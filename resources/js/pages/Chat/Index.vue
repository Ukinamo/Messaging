<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';
import { useChat } from '@/composables/useChat';
import type { ChatPageProps } from '@/types';
import { MessageSquare } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import ChatHeader from '@/components/chat/ChatHeader.vue';
import ConversationList from '@/components/chat/ConversationList.vue';
import ForwardMessageDialog from '@/components/chat/ForwardMessageDialog.vue';
import MessageComposer from '@/components/chat/MessageComposer.vue';
import MessageThread from '@/components/chat/MessageThread.vue';
import NewConversationDialog from '@/components/chat/NewConversationDialog.vue';

const props = defineProps<ChatPageProps>();

const breadcrumbs = [{ title: 'Chat', href: '/chat' }];

const {
    authUserId,
    conversations,
    activeConversation,
    messages,
    loadingMore,
    hasMore,
    typingNames,
    onlineUserIds,
    sendError,
    readByOthersUpToId,
    sendMessage,
    loadMoreMessages,
    sendTyping,
    deleteForMe,
    deleteForEveryone,
    reactToMessage,
    forwardMessage,
    createConversation,
    refreshConversations,
    joinConversationChannel,
    markAsRead,
} = useChat(props.conversations, props.activeConversation);

const showNewChatDialog = ref(false);
const showForwardDialog = ref(false);
const forwardingMessageId = ref<number | null>(null);
const mobileShowThread = ref(!!props.activeConversation);
const threadRef = ref<InstanceType<typeof MessageThread> | null>(null);

const isOnline = computed(() => {
    if (!activeConversation.value) return false;
    return activeConversation.value.participants.some(
        (p) => p.id !== authUserId.value && onlineUserIds.value.has(p.id),
    );
});

function handleSelectConversation(conv: { id: number }) {
    if (activeConversation.value?.id === conv.id) return;
    router.visit(`/chat/${conv.id}`, {
        preserveState: false,
        preserveScroll: false,
    });
    mobileShowThread.value = true;
}

async function handleNewChatUser(userId: number) {
    const conversationId = await createConversation(userId);
    if (conversationId) {
        router.visit(`/chat/${conversationId}`, { preserveState: false });
        mobileShowThread.value = true;
    }
}

async function handleSend(body: string, attachments?: File[]) {
    if (!activeConversation.value) return;
    await sendMessage(activeConversation.value.id, body, attachments);
    threadRef.value?.scrollToBottom(true);
}

function handleTyping() {
    if (!activeConversation.value) return;
    sendTyping(activeConversation.value.id);
}

function handleLoadMore() {
    if (!activeConversation.value) return;
    loadMoreMessages(activeConversation.value.id);
}

function handleBack() {
    mobileShowThread.value = false;
}

function handleForwardRequest(messageId: number) {
    forwardingMessageId.value = messageId;
    showForwardDialog.value = true;
}

async function handleForwardSelect(targetConversationId: number) {
    if (forwardingMessageId.value !== null) {
        await forwardMessage(forwardingMessageId.value, targetConversationId);
        forwardingMessageId.value = null;
    }
}
</script>

<template>
    <Head title="Chat" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100vh-4rem)] overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <!-- Conversation list panel -->
            <div
                :class="
                    cn(
                        'bg-background w-full shrink-0 border-r md:w-80 lg:w-96',
                        mobileShowThread ? 'hidden md:block' : 'block',
                    )
                "
            >
                <ConversationList
                    :conversations="conversations"
                    :active-conversation-id="activeConversation?.id"
                    :online-user-ids="onlineUserIds"
                    :auth-user-id="authUserId"
                    @select="handleSelectConversation"
                    @new-chat="showNewChatDialog = true"
                />
            </div>

            <!-- Message thread panel -->
            <div
                :class="
                    cn(
                        'bg-background flex min-w-0 flex-1 flex-col',
                        !mobileShowThread ? 'hidden md:flex' : 'flex',
                    )
                "
            >
                <template v-if="activeConversation">
                    <ChatHeader
                        :conversation="activeConversation"
                        :online="isOnline"
                        :typing-names="typingNames"
                        @back="handleBack"
                    />

                    <MessageThread
                        ref="threadRef"
                        :messages="messages"
                        :auth-user-id="authUserId"
                        :loading-more="loadingMore"
                        :has-more="hasMore"
                        :show-sender-names="activeConversation.type === 'group'"
                        :read-by-others-up-to-id="readByOthersUpToId"
                        @load-more="handleLoadMore"
                        @delete-for-me="deleteForMe"
                        @delete-for-everyone="deleteForEveryone"
                        @react="reactToMessage"
                        @forward="handleForwardRequest"
                    />

                    <div v-if="sendError" class="bg-destructive/10 text-destructive flex items-center justify-between px-4 py-2 text-xs">
                        <span>{{ sendError }}</span>
                        <button class="hover:underline" @click="sendError = null">Dismiss</button>
                    </div>

                    <MessageComposer
                        @send="handleSend"
                        @typing="handleTyping"
                    />
                </template>

                <!-- Empty state -->
                <div v-else class="flex flex-1 flex-col items-center justify-center gap-3 p-8">
                    <div class="bg-muted flex size-16 items-center justify-center rounded-full">
                        <MessageSquare class="text-muted-foreground size-8" />
                    </div>
                    <h3 class="text-lg font-semibold">Your Messages</h3>
                    <p class="text-muted-foreground max-w-sm text-center text-sm">
                        Select a conversation from the list or start a new one to begin chatting.
                    </p>
                    <button
                        class="bg-primary text-primary-foreground hover:bg-primary/90 mt-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
                        @click="showNewChatDialog = true"
                    >
                        Start a Conversation
                    </button>
                </div>
            </div>
        </div>

        <NewConversationDialog
            :open="showNewChatDialog"
            @update:open="showNewChatDialog = $event"
            @select-user="handleNewChatUser"
        />

        <ForwardMessageDialog
            :open="showForwardDialog"
            :conversations="conversations"
            :active-conversation-id="activeConversation?.id"
            @update:open="showForwardDialog = $event"
            @select="handleForwardSelect"
        />
    </AppLayout>
</template>
