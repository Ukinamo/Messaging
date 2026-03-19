<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';
import { useCall } from '@/composables/useCall';
import { useChat } from '@/composables/useChat';
import type { CallData, ChatPageProps } from '@/types';
import { MessageSquare } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import CallOverlay from '@/components/chat/CallOverlay.vue';
import ChatHeader from '@/components/chat/ChatHeader.vue';
import ConversationList from '@/components/chat/ConversationList.vue';
import ForwardMessageDialog from '@/components/chat/ForwardMessageDialog.vue';
import MessageComposer from '@/components/chat/MessageComposer.vue';
import MessageThread from '@/components/chat/MessageThread.vue';
import NewConversationDialog from '@/components/chat/NewConversationDialog.vue';
import UserProfilePanel from '@/components/chat/UserProfilePanel.vue';

const props = defineProps<ChatPageProps>();

const breadcrumbs = [{ title: 'Chat', href: '/chat' }];

function onIncomingCall(data: CallData) {
    handleIncomingCall(data);
}

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
    archiveConversation,
    unarchiveConversation,
    blockUser,
    unblockUser,
    createConversation,
    refreshConversations,
    joinConversationChannel,
    markAsRead,
} = useChat(props.conversations, props.activeConversation, onIncomingCall);

const {
    callState,
    callDuration,
    isMuted,
    error: callError,
    otherParticipant: callOtherParticipant,
    initiateCall,
    handleIncomingCall,
    answerCall,
    rejectCall,
    endCall,
    toggleMute,
} = useCall(() => authUserId.value);

function handleCallClick() {
    if (!activeConversation.value) return;
    initiateCall(activeConversation.value.id);
}

const showNewChatDialog = ref(false);
const showForwardDialog = ref(false);
const showProfilePanel = ref(false);
const forwardingMessageId = ref<number | null>(null);
const mobileShowThread = ref(!!props.activeConversation);
const threadRef = ref<InstanceType<typeof MessageThread> | null>(null);
const localIsArchived = ref(props.activeConversation?.is_archived ?? false);
const localIsBlocked = ref(props.activeConversation?.is_blocked ?? false);

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

async function handleArchive() {
    if (!activeConversation.value) return;
    const ok = await archiveConversation(activeConversation.value.id);
    if (ok) {
        localIsArchived.value = true;
        showProfilePanel.value = false;
        router.visit('/chat', { preserveState: false });
    }
}

async function handleUnarchive() {
    if (!activeConversation.value) return;
    const ok = await unarchiveConversation(activeConversation.value.id);
    if (ok) {
        localIsArchived.value = false;
    }
}

async function handleBlock(userId: number) {
    const ok = await blockUser(userId);
    if (ok) {
        localIsBlocked.value = true;
    }
}

async function handleUnblock(userId: number) {
    const ok = await unblockUser(userId);
    if (ok) {
        localIsBlocked.value = false;
    }
}
</script>

<template>
    <Head title="Chat" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100vh-4rem)] overflow-hidden rounded-xl border border-sidebar-border/70 shadow-sm dark:border-sidebar-border">
            <!-- Conversation list panel -->
            <div
                :class="
                    cn(
                        'bg-background/50 w-full shrink-0 border-r md:w-80 lg:w-96',
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

            <!-- Message thread panel + profile panel -->
            <div
                :class="
                    cn(
                        'bg-background flex min-w-0 flex-1',
                        !mobileShowThread ? 'hidden md:flex' : 'flex',
                    )
                "
            >
                <div v-if="activeConversation" class="flex min-w-0 flex-1 flex-col">
                    <ChatHeader
                        :conversation="activeConversation"
                        :online="isOnline"
                        :typing-names="typingNames"
                        @back="handleBack"
                        @open-profile="showProfilePanel = !showProfilePanel"
                        @call="handleCallClick"
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
                </div>

                <!-- Empty state -->
                <div v-else class="flex flex-1 flex-col items-center justify-center gap-4 p-8">
                    <div class="bg-muted/50 flex size-20 items-center justify-center rounded-full">
                        <MessageSquare class="text-muted-foreground/60 size-9" />
                    </div>
                    <div class="text-center">
                        <h3 class="text-lg font-semibold">Your Messages</h3>
                        <p class="text-muted-foreground mt-1 max-w-xs text-sm leading-relaxed">
                            Select a conversation from the list or start a new one to begin chatting.
                        </p>
                    </div>
                    <button
                        class="bg-primary text-primary-foreground hover:bg-primary/90 mt-1 rounded-full px-5 py-2.5 text-sm font-medium shadow-sm transition-all hover:shadow-md"
                        @click="showNewChatDialog = true"
                    >
                        Start a Conversation
                    </button>
                </div>

                <!-- Profile panel slide-over -->
                <UserProfilePanel
                    v-if="activeConversation && showProfilePanel"
                    :conversation="activeConversation"
                    :auth-user-id="authUserId"
                    :is-archived="localIsArchived"
                    :is-blocked="localIsBlocked"
                    @close="showProfilePanel = false"
                    @archive="handleArchive"
                    @unarchive="handleUnarchive"
                    @block="handleBlock"
                    @unblock="handleUnblock"
                />
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

        <CallOverlay
            :state="callState"
            :other-participant="callOtherParticipant"
            :duration="callDuration"
            :muted="isMuted"
            :error="callError"
            @answer="answerCall"
            @reject="rejectCall"
            @end="endCall"
            @toggle-mute="toggleMute"
        />
    </AppLayout>
</template>
