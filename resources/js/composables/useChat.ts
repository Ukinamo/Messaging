import echo from '@/echo';
import { usePresence } from '@/composables/usePresence';
import type { ActiveConversation, CallData, ChatMessage, ConversationSummary, ReactionGroup } from '@/types';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

export type CallInitiatedHandler = (data: CallData) => void;

export function useChat(
    initialConversations: ConversationSummary[],
    initialActive?: ActiveConversation,
    onCallInitiated?: CallInitiatedHandler,
) {
    const page = usePage();
    const authUserId = computed(() => page.props.auth.user.id);

    const conversations = ref<ConversationSummary[]>([...initialConversations]);
    const activeConversation = ref<ActiveConversation | undefined>(
        initialActive ? { ...initialActive, messages: [...initialActive.messages] } : undefined,
    );
    const messages = ref<ChatMessage[]>(initialActive?.messages ? [...initialActive.messages] : []);
    const loadingMore = ref(false);
    const hasMore = ref(true);
    const typingUsers = ref<Map<number, { name: string; timeout: ReturnType<typeof setTimeout> }>>(new Map());
    const { onlineUserIds } = usePresence();

    const readByOthersUpToId = ref<number>(initialActive?.last_read_by_others ?? 0);

    let currentChannelName: string | null = null;
    const userChannelName = computed(() => `user.${authUserId.value}`);

    function joinUserChannel() {
        echo.private(userChannelName.value).listen('MessageSent', (e: { message: ChatMessage }) => {
            // If the active conversation is the same, the conversation channel will handle it.
            // This ensures sidebar/unread updates even when user isn't inside the conversation.
            if (activeConversation.value?.id !== e.message.conversation_id) {
                updateConversationPreview(e.message);
            }
        });
    }

    function leaveUserChannel() {
        echo.leave(userChannelName.value);
    }

    function joinConversationChannel(conversationId: number) {
        if (currentChannelName) {
            echo.leave(currentChannelName);
        }

        currentChannelName = `conversation.${conversationId}`;

        echo.private(currentChannelName)
            .listen('MessageSent', (e: { message: ChatMessage }) => {
                handleIncomingMessage(e.message);
            })
            .listen('MessageRead', (e: { user_id: number; last_read_message_id: number }) => {
                handleReadReceipt(e.user_id, e.last_read_message_id);
            })
            .listen('UserTyping', (e: { user_id: number; user_name: string; is_typing: boolean }) => {
                handleTypingIndicator(e.user_id, e.user_name, e.is_typing);
            })
            .listen('MessageDeleted', (e: { message_id: number }) => {
                const idx = messages.value.findIndex((m) => m.id === e.message_id);
                if (idx !== -1) {
                    messages.value[idx] = {
                        ...messages.value[idx],
                        deleted_for_everyone: true,
                        body: null,
                        metadata: null,
                        reactions: [],
                    };
                }
            })
            .listen('MessageReacted', (e: { message_id: number; reactions: ReactionGroup[] }) => {
                handleReactionUpdate(e.message_id, e.reactions);
            })
            .listen('CallInitiated', (e: { call: CallData }) => {
                if (onCallInitiated && e.call.caller.id !== authUserId.value) {
                    onCallInitiated(e.call);
                }
            });
    }

    function leaveConversationChannel() {
        if (currentChannelName) {
            echo.leave(currentChannelName);
            currentChannelName = null;
        }
    }

    function handleIncomingMessage(message: ChatMessage) {
        if (activeConversation.value && message.conversation_id === activeConversation.value.id) {
            const exists = messages.value.some((m) => m.id === message.id);
            if (!exists) {
                messages.value.push(message);
            }
            markAsRead(message.conversation_id);
        }

        updateConversationPreview(message);
    }

    function updateConversationPreview(message: ChatMessage) {
        const idx = conversations.value.findIndex((c) => c.id === message.conversation_id);
        if (idx !== -1) {
            const conv = { ...conversations.value[idx] };
            conv.latest_message = {
                id: message.id,
                body: message.body,
                type: message.type,
                sender_name: message.sender?.name ?? null,
                created_at: message.created_at,
            };

            if (
                !activeConversation.value ||
                message.conversation_id !== activeConversation.value.id
            ) {
                conv.unread_count += 1;
            }

            conversations.value.splice(idx, 1);
            conversations.value.unshift(conv);
        }
    }

    function handleReadReceipt(userId: number, lastReadMessageId: number) {
        if (userId === authUserId.value) return;
        if (lastReadMessageId > readByOthersUpToId.value) {
            readByOthersUpToId.value = lastReadMessageId;
        }
    }

    function handleTypingIndicator(userId: number, userName: string, isTyping: boolean) {
        if (isTyping) {
            const existing = typingUsers.value.get(userId);
            if (existing) clearTimeout(existing.timeout);

            const timeout = setTimeout(() => {
                typingUsers.value.delete(userId);
                typingUsers.value = new Map(typingUsers.value);
            }, 3000);

            typingUsers.value.set(userId, { name: userName, timeout });
            typingUsers.value = new Map(typingUsers.value);
        } else {
            const existing = typingUsers.value.get(userId);
            if (existing) clearTimeout(existing.timeout);
            typingUsers.value.delete(userId);
            typingUsers.value = new Map(typingUsers.value);
        }
    }

    const sendError = ref<string | null>(null);

    async function sendMessage(
        conversationId: number,
        body: string,
        attachments?: File[],
    ): Promise<ChatMessage | null> {
        sendError.value = null;
        const formData = new FormData();
        if (body.trim()) formData.append('body', body);
        if (attachments) {
            attachments.forEach((f) => formData.append('attachments[]', f));
        }

        try {
            const { data } = await axios.post(
                `/api/chat/conversations/${conversationId}/messages`,
                formData,
                { headers: { 'Content-Type': 'multipart/form-data' } },
            );

            const msg = data.message as ChatMessage;
            const exists = messages.value.some((m) => m.id === msg.id);
            if (!exists) {
                messages.value.push(msg);
            }

            updateConversationPreview(msg);
            return msg;
        } catch (err: unknown) {
            if (axios.isAxiosError(err)) {
                sendError.value = err.response?.data?.error ?? 'Failed to send message.';
            } else {
                sendError.value = 'Failed to send message.';
            }
            return null;
        }
    }

    async function loadMoreMessages(conversationId: number) {
        if (loadingMore.value || !hasMore.value) return;
        loadingMore.value = true;

        const firstMsgId = messages.value[0]?.id;
        try {
            const { data } = await axios.get(
                `/api/chat/conversations/${conversationId}/messages`,
                { params: { before: firstMsgId } },
            );

            const older = data.messages as ChatMessage[];
            messages.value.unshift(...older);
            hasMore.value = data.has_more;
        } finally {
            loadingMore.value = false;
        }
    }

    async function markAsRead(conversationId: number) {
        try {
            await axios.post(`/api/chat/conversations/${conversationId}/read`);
            const idx = conversations.value.findIndex((c) => c.id === conversationId);
            if (idx !== -1) {
                conversations.value[idx] = { ...conversations.value[idx], unread_count: 0 };
            }
        } catch {
            // silent fail
        }
    }

    let typingTimeout: ReturnType<typeof setTimeout> | null = null;

    async function sendTyping(conversationId: number) {
        if (typingTimeout) return;
        try {
            await axios.post(`/api/chat/conversations/${conversationId}/typing`, { is_typing: true });
        } catch {
            // silent
        }
        typingTimeout = setTimeout(() => {
            typingTimeout = null;
        }, 2000);
    }

    async function stopTyping(conversationId: number) {
        if (typingTimeout) {
            clearTimeout(typingTimeout);
            typingTimeout = null;
        }
        try {
            await axios.post(`/api/chat/conversations/${conversationId}/typing`, { is_typing: false });
        } catch {
            // silent
        }
    }

    function handleReactionUpdate(messageId: number, reactions: ReactionGroup[]) {
        const idx = messages.value.findIndex((m) => m.id === messageId);
        if (idx !== -1) {
            messages.value[idx] = { ...messages.value[idx], reactions };
        }
    }

    async function deleteMessage(messageId: number) {
        try {
            await axios.delete(`/api/chat/messages/${messageId}`);
            messages.value = messages.value.filter((m) => m.id !== messageId);
        } catch {
            // silent
        }
    }

    async function deleteForMe(messageId: number) {
        try {
            await axios.delete(`/api/chat/messages/${messageId}`, {
                data: { mode: 'for_me' },
            });
            messages.value = messages.value.filter((m) => m.id !== messageId);
        } catch {
            // silent
        }
    }

    async function deleteForEveryone(messageId: number) {
        try {
            await axios.delete(`/api/chat/messages/${messageId}`, {
                data: { mode: 'for_everyone' },
            });
            const idx = messages.value.findIndex((m) => m.id === messageId);
            if (idx !== -1) {
                messages.value[idx] = {
                    ...messages.value[idx],
                    deleted_for_everyone: true,
                    body: null,
                    metadata: null,
                    reactions: [],
                };
            }
        } catch {
            // silent
        }
    }

    async function reactToMessage(messageId: number, emoji: string) {
        try {
            const { data } = await axios.post(`/api/chat/messages/${messageId}/react`, { emoji });
            handleReactionUpdate(messageId, data.reactions);
        } catch {
            // silent
        }
    }

    async function forwardMessage(messageId: number, targetConversationId: number) {
        try {
            await axios.post(`/api/chat/messages/${messageId}/forward`, {
                conversation_id: targetConversationId,
            });
            return true;
        } catch {
            return false;
        }
    }

    async function archiveConversation(conversationId: number) {
        try {
            await axios.post(`/api/chat/conversations/${conversationId}/archive`);
            conversations.value = conversations.value.filter((c) => c.id !== conversationId);
            if (activeConversation.value?.id === conversationId) {
                clearActiveConversation();
            }
            return true;
        } catch {
            return false;
        }
    }

    async function unarchiveConversation(conversationId: number) {
        try {
            await axios.delete(`/api/chat/conversations/${conversationId}/archive`);
            return true;
        } catch {
            return false;
        }
    }

    async function blockUser(userId: number) {
        try {
            await axios.post(`/api/chat/block/${userId}`);
            return true;
        } catch {
            return false;
        }
    }

    async function unblockUser(userId: number) {
        try {
            await axios.delete(`/api/chat/block/${userId}`);
            return true;
        } catch {
            return false;
        }
    }

    async function createConversation(userId: number): Promise<number | null> {
        try {
            const { data } = await axios.post('/api/chat/conversations', { user_id: userId });
            return data.conversation_id;
        } catch {
            return null;
        }
    }

    async function searchUsers(query: string) {
        try {
            const { data } = await axios.get('/api/chat/users/search', { params: { q: query } });
            return data.users as Array<{ id: number; name: string; email: string; avatar: string | null }>;
        } catch {
            return [];
        }
    }

    async function refreshConversations() {
        try {
            const { data } = await axios.get('/api/chat/conversations');
            conversations.value = data.conversations;
        } catch {
            // silent
        }
    }

    function setActiveConversation(conv: ActiveConversation) {
        activeConversation.value = conv;
        messages.value = [...conv.messages];
        hasMore.value = true;
        readByOthersUpToId.value = conv.last_read_by_others ?? 0;
        typingUsers.value.clear();
        joinConversationChannel(conv.id);
        markAsRead(conv.id);
    }

    function clearActiveConversation() {
        leaveConversationChannel();
        activeConversation.value = undefined;
        messages.value = [];
        typingUsers.value.clear();
    }

    watch(
        () => initialActive,
        (newVal) => {
            if (newVal) {
                setActiveConversation(newVal);
            }
        },
        { immediate: true },
    );

    joinUserChannel();

    onBeforeUnmount(() => {
        leaveUserChannel();
        leaveConversationChannel();
    });

    const typingNames = computed(() => {
        return Array.from(typingUsers.value.values()).map((u) => u.name);
    });

    const totalUnread = computed(() => {
        return conversations.value.reduce((sum, c) => sum + c.unread_count, 0);
    });

    return {
        authUserId,
        conversations,
        activeConversation,
        messages,
        loadingMore,
        hasMore,
        typingNames,
        onlineUserIds,
        totalUnread,
        sendError,
        readByOthersUpToId,
        sendMessage,
        loadMoreMessages,
        markAsRead,
        sendTyping,
        stopTyping,
        deleteMessage,
        deleteForMe,
        deleteForEveryone,
        reactToMessage,
        forwardMessage,
        archiveConversation,
        unarchiveConversation,
        blockUser,
        unblockUser,
        createConversation,
        searchUsers,
        refreshConversations,
        setActiveConversation,
        clearActiveConversation,
        joinConversationChannel,
    };
}
