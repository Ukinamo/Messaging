import echo from '@/echo';
import type { CallData, CallState, ChatParticipant } from '@/types';
import axios from 'axios';
import { computed, onBeforeUnmount, ref } from 'vue';

const ICE_SERVERS: RTCConfiguration = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
    ],
};

export function useCall(authUserId: () => number) {
    const callState = ref<CallState>('idle');
    const callData = ref<CallData | null>(null);
    const isMuted = ref(false);
    const callDuration = ref(0);
    const error = ref<string | null>(null);

    let peerConnection: RTCPeerConnection | null = null;
    let localStream: MediaStream | null = null;
    let remoteAudio: HTMLAudioElement | null = null;
    let durationInterval: ReturnType<typeof setInterval> | null = null;
    let callChannelName: string | null = null;
    let ringtoneTimeout: ReturnType<typeof setTimeout> | null = null;
    const userChannelName = computed(() => `user.${authUserId()}`);

    const otherParticipant = computed<ChatParticipant | null>(() => {
        if (!callData.value) return null;
        const uid = authUserId();
        return callData.value.caller.id === uid
            ? callData.value.receiver
            : callData.value.caller;
    });

    function joinCallChannel(callId: number) {
        if (callChannelName) {
            echo.leave(callChannelName);
        }
        callChannelName = `call.${callId}`;

        echo.private(callChannelName)
            .listen('CallAnswered', handleCallAnswered)
            .listen('CallRejected', handleCallRejected)
            .listen('CallEnded', handleCallEnded)
            .listen('CallSignal', handleSignal);
    }

    function joinUserChannel() {
        echo.private(userChannelName.value).listen('CallInitiated', (e: { call: CallData }) => {
            handleIncomingCall(e.call);
        });
    }

    function leaveUserChannel() {
        echo.leave(userChannelName.value);
    }

    function leaveCallChannel() {
        if (callChannelName) {
            echo.leave(callChannelName);
            callChannelName = null;
        }
    }

    async function getMicrophone(): Promise<MediaStream> {
        const host = window.location.hostname;
        const isLocalhost = host === 'localhost' || host === '127.0.0.1';
        if (!window.isSecureContext && !isLocalhost) {
            throw new Error(
                'Voice calling requires a secure context (HTTPS) or localhost for microphone access.',
            );
        }

        if (!navigator.mediaDevices?.getUserMedia) {
            throw new Error('Your browser does not support microphone access (getUserMedia).');
        }

        try {
            return await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
        } catch (e: any) {
            const name = e?.name as string | undefined;
            if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
                throw new Error('Microphone access denied. Please allow microphone permissions.');
            }
            if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
                throw new Error('No microphone device found.');
            }
            throw new Error('Unable to access microphone.');
        }
    }

    function createPeerConnection(): RTCPeerConnection {
        const pc = new RTCPeerConnection(ICE_SERVERS);

        pc.onicecandidate = (event) => {
            if (event.candidate && callData.value) {
                axios.post(`/api/chat/calls/${callData.value.id}/signal`, {
                    type: 'ice-candidate',
                    payload: { candidate: event.candidate.toJSON() },
                }).catch(() => {});
            }
        };

        pc.ontrack = (event) => {
            if (!remoteAudio) {
                remoteAudio = new Audio();
                remoteAudio.autoplay = true;
            }
            remoteAudio.srcObject = event.streams[0];
        };

        pc.onconnectionstatechange = () => {
            if (pc.connectionState === 'disconnected' || pc.connectionState === 'failed') {
                endCall();
            }
        };

        return pc;
    }

    async function initiateCall(conversationId: number) {
        if (callState.value !== 'idle') return;

        error.value = null;

        try {
            localStream = await getMicrophone();
        } catch (e: any) {
            error.value = e.message;
            return;
        }

        try {
            const { data } = await axios.post(`/api/chat/conversations/${conversationId}/call`);

            callData.value = data.call as CallData;
            callState.value = 'outgoing';
            joinCallChannel(data.call.id);

            ringtoneTimeout = setTimeout(() => {
                if (callState.value === 'outgoing') {
                    endCall();
                }
            }, 45000);
        } catch (e: any) {
            cleanup();
            error.value = e.response?.data?.error || 'Failed to initiate call.';
        }
    }

    function handleIncomingCall(data: CallData) {
        if (callState.value !== 'idle') return;

        callData.value = data;
        callState.value = 'incoming';
        joinCallChannel(data.id);

        ringtoneTimeout = setTimeout(() => {
            if (callState.value === 'incoming') {
                rejectCall();
            }
        }, 45000);
    }

    async function answerCall() {
        if (callState.value !== 'incoming' || !callData.value) return;

        error.value = null;

        try {
            localStream = await getMicrophone();
        } catch (e: any) {
            error.value = e.message;
            return;
        }

        try {
            await axios.post(`/api/chat/calls/${callData.value.id}/answer`);
            callState.value = 'active';
            startDurationTimer();

            if (ringtoneTimeout) {
                clearTimeout(ringtoneTimeout);
                ringtoneTimeout = null;
            }
        } catch (e: any) {
            cleanup();
            error.value = 'Failed to answer call.';
        }
    }

    async function rejectCall() {
        if (!callData.value) return;

        try {
            await axios.post(`/api/chat/calls/${callData.value.id}/reject`);
        } catch {}
        cleanup();
    }

    async function endCall() {
        if (!callData.value) return;

        try {
            await axios.post(`/api/chat/calls/${callData.value.id}/end`);
        } catch {}
        cleanup();
    }

    function handleCallAnswered() {
        if (callState.value !== 'outgoing' || !callData.value) return;

        callState.value = 'active';
        startDurationTimer();

        if (ringtoneTimeout) {
            clearTimeout(ringtoneTimeout);
            ringtoneTimeout = null;
        }

        startWebRTC(true);
    }

    function handleCallRejected() {
        cleanup();
    }

    function handleCallEnded() {
        cleanup();
    }

    async function handleSignal(event: { from_user_id: number; type: string; payload: any }) {
        if (event.from_user_id === authUserId()) return;

        if (event.type === 'offer') {
            peerConnection = createPeerConnection();
            if (localStream) {
                localStream.getTracks().forEach((track) => {
                    peerConnection!.addTrack(track, localStream!);
                });
            }

            await peerConnection.setRemoteDescription(new RTCSessionDescription(event.payload.sdp));
            const answer = await peerConnection.createAnswer();
            await peerConnection.setLocalDescription(answer);

            if (callData.value) {
                await axios.post(`/api/chat/calls/${callData.value.id}/signal`, {
                    type: 'answer',
                    payload: { sdp: answer },
                }).catch(() => {});
            }
        } else if (event.type === 'answer') {
            if (peerConnection) {
                await peerConnection.setRemoteDescription(new RTCSessionDescription(event.payload.sdp));
            }
        } else if (event.type === 'ice-candidate') {
            if (peerConnection && event.payload.candidate) {
                await peerConnection.addIceCandidate(new RTCIceCandidate(event.payload.candidate)).catch(() => {});
            }
        }
    }

    async function startWebRTC(isCaller: boolean) {
        peerConnection = createPeerConnection();

        if (localStream) {
            localStream.getTracks().forEach((track) => {
                peerConnection!.addTrack(track, localStream!);
            });
        }

        if (isCaller) {
            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);

            if (callData.value) {
                await axios.post(`/api/chat/calls/${callData.value.id}/signal`, {
                    type: 'offer',
                    payload: { sdp: offer },
                }).catch(() => {});
            }
        }
    }

    function toggleMute() {
        isMuted.value = !isMuted.value;
        if (localStream) {
            localStream.getAudioTracks().forEach((track) => {
                track.enabled = !isMuted.value;
            });
        }
    }

    function startDurationTimer() {
        callDuration.value = 0;
        durationInterval = setInterval(() => {
            callDuration.value++;
        }, 1000);
    }

    function cleanup() {
        if (ringtoneTimeout) {
            clearTimeout(ringtoneTimeout);
            ringtoneTimeout = null;
        }

        if (durationInterval) {
            clearInterval(durationInterval);
            durationInterval = null;
        }

        if (peerConnection) {
            peerConnection.close();
            peerConnection = null;
        }

        if (localStream) {
            localStream.getTracks().forEach((t) => t.stop());
            localStream = null;
        }

        if (remoteAudio) {
            remoteAudio.srcObject = null;
            remoteAudio = null;
        }

        leaveCallChannel();

        callState.value = 'idle';
        callData.value = null;
        callDuration.value = 0;
        isMuted.value = false;
        error.value = null;
    }

    onBeforeUnmount(() => {
        cleanup();
        leaveUserChannel();
    });

    // Start listening immediately for incoming calls.
    joinUserChannel();

    return {
        callState,
        callData,
        callDuration,
        isMuted,
        error,
        otherParticipant,
        initiateCall,
        handleIncomingCall,
        answerCall,
        rejectCall,
        endCall,
        toggleMute,
    };
}
