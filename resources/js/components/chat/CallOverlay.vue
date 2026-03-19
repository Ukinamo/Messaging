<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { CallState, ChatParticipant } from '@/types';
import { Mic, MicOff, Phone, PhoneOff, X } from 'lucide-vue-next';
import { computed } from 'vue';
import ChatAvatar from './ChatAvatar.vue';

const props = defineProps<{
    state: CallState;
    otherParticipant: ChatParticipant | null;
    duration: number;
    muted: boolean;
    error: string | null;
}>();

const emit = defineEmits<{
    answer: [];
    reject: [];
    end: [];
    toggleMute: [];
}>();

const formattedDuration = computed(() => {
    const m = Math.floor(props.duration / 60);
    const s = props.duration % 60;
    return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
});

const statusLabel = computed(() => {
    if (props.state === 'outgoing') return 'Calling...';
    if (props.state === 'incoming') return 'Incoming call';
    if (props.state === 'active') return formattedDuration.value;
    return '';
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="state !== 'idle'"
            class="fixed inset-0 z-200 flex items-center justify-center bg-black/70 backdrop-blur-md"
        >
            <div class="flex w-full max-w-xs flex-col items-center gap-6 rounded-3xl bg-zinc-900 px-8 pb-8 pt-10 shadow-2xl">
                <!-- Other user info -->
                <div class="flex flex-col items-center gap-3">
                    <div :class="cn(
                        'rounded-full p-1',
                        state === 'active' ? 'ring-4 ring-emerald-500/30' : state === 'incoming' ? 'animate-pulse ring-4 ring-blue-500/30' : 'ring-4 ring-zinc-700',
                    )">
                        <ChatAvatar
                            :name="otherParticipant?.name || 'User'"
                            :avatar="otherParticipant?.avatar"
                            size="lg"
                        />
                    </div>
                    <div class="text-center">
                        <h3 class="text-lg font-semibold text-white">
                            {{ otherParticipant?.name || 'User' }}
                        </h3>
                        <p :class="cn(
                            'mt-0.5 text-sm',
                            state === 'active' ? 'text-emerald-400 font-mono tabular-nums' : 'text-zinc-400',
                        )">
                            {{ statusLabel }}
                        </p>
                    </div>
                </div>

                <!-- Error -->
                <p v-if="error" class="text-center text-xs text-red-400">
                    {{ error }}
                </p>

                <!-- Outgoing call: pulse ring animation -->
                <div v-if="state === 'outgoing'" class="relative flex size-16 items-center justify-center">
                    <span class="absolute inset-0 animate-ping rounded-full bg-zinc-600/40" />
                    <span class="absolute inset-2 animate-pulse rounded-full bg-zinc-600/20" />
                    <Phone class="relative size-6 text-zinc-300" />
                </div>

                <!-- Controls -->
                <div class="flex items-center gap-4">
                    <!-- Incoming: Accept + Reject -->
                    <template v-if="state === 'incoming'">
                        <button
                            class="flex size-14 items-center justify-center rounded-full bg-red-500 text-white shadow-lg transition-transform hover:scale-105 hover:bg-red-600 active:scale-95"
                            title="Reject"
                            @click="emit('reject')"
                        >
                            <X class="size-6" />
                        </button>
                        <button
                            class="flex size-14 items-center justify-center rounded-full bg-emerald-500 text-white shadow-lg transition-transform hover:scale-105 hover:bg-emerald-600 active:scale-95"
                            title="Accept"
                            @click="emit('answer')"
                        >
                            <Phone class="size-6" />
                        </button>
                    </template>

                    <!-- Active: Mute + End -->
                    <template v-if="state === 'active'">
                        <button
                            :class="cn(
                                'flex size-12 items-center justify-center rounded-full transition-transform hover:scale-105 active:scale-95',
                                muted
                                    ? 'bg-red-500/20 text-red-400 ring-1 ring-red-500/40'
                                    : 'bg-zinc-700 text-zinc-200 hover:bg-zinc-600',
                            )"
                            :title="muted ? 'Unmute' : 'Mute'"
                            @click="emit('toggleMute')"
                        >
                            <MicOff v-if="muted" class="size-5" />
                            <Mic v-else class="size-5" />
                        </button>
                        <button
                            class="flex size-14 items-center justify-center rounded-full bg-red-500 text-white shadow-lg transition-transform hover:scale-105 hover:bg-red-600 active:scale-95"
                            title="End call"
                            @click="emit('end')"
                        >
                            <PhoneOff class="size-6" />
                        </button>
                    </template>

                    <!-- Outgoing: Cancel -->
                    <template v-if="state === 'outgoing'">
                        <button
                            class="flex size-14 items-center justify-center rounded-full bg-red-500 text-white shadow-lg transition-transform hover:scale-105 hover:bg-red-600 active:scale-95"
                            title="Cancel"
                            @click="emit('end')"
                        >
                            <PhoneOff class="size-6" />
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </Teleport>
</template>
