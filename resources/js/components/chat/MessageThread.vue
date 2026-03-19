<script setup lang="ts">
import { Spinner } from '@/components/ui/spinner';
import type { ChatMessage } from '@/types';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import MessageBubble from './MessageBubble.vue';

const props = defineProps<{
    messages: ChatMessage[];
    authUserId: number;
    loadingMore: boolean;
    hasMore: boolean;
    showSenderNames?: boolean;
    readByOthersUpToId?: number;
}>();

const emit = defineEmits<{
    loadMore: [];
    deleteForMe: [messageId: number];
    deleteForEveryone: [messageId: number];
    react: [messageId: number, emoji: string];
    forward: [messageId: number];
}>();

const containerRef = ref<HTMLElement | null>(null);
const shouldAutoScroll = ref(true);

function isNewDay(current: ChatMessage, previous?: ChatMessage): boolean {
    if (!previous) return true;
    const a = new Date(current.created_at).toDateString();
    const b = new Date(previous.created_at).toDateString();
    return a !== b;
}

function formatDateLabel(dateStr: string): string {
    const date = new Date(dateStr);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    if (date.toDateString() === today.toDateString()) return 'Today';
    if (date.toDateString() === yesterday.toDateString()) return 'Yesterday';
    return date.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric' });
}

const groupedMessages = computed(() => {
    const groups: Array<{ date: string; messages: ChatMessage[] }> = [];
    let currentGroup: { date: string; messages: ChatMessage[] } | null = null;

    for (const msg of props.messages) {
        const dateStr = new Date(msg.created_at).toDateString();
        if (!currentGroup || currentGroup.date !== dateStr) {
            currentGroup = { date: dateStr, messages: [] };
            groups.push(currentGroup);
        }
        currentGroup.messages.push(msg);
    }
    return groups;
});

function scrollToBottom(smooth = false) {
    nextTick(() => {
        if (containerRef.value) {
            containerRef.value.scrollTo({
                top: containerRef.value.scrollHeight,
                behavior: smooth ? 'smooth' : 'instant',
            });
        }
    });
}

function onScroll() {
    if (!containerRef.value) return;
    const { scrollTop, scrollHeight, clientHeight } = containerRef.value;
    shouldAutoScroll.value = scrollHeight - scrollTop - clientHeight < 100;

    if (scrollTop < 80 && props.hasMore && !props.loadingMore) {
        const prevHeight = containerRef.value.scrollHeight;
        emit('loadMore');
        nextTick(() => {
            if (containerRef.value) {
                containerRef.value.scrollTop = containerRef.value.scrollHeight - prevHeight;
            }
        });
    }
}

watch(
    () => props.messages.length,
    () => {
        if (shouldAutoScroll.value) {
            scrollToBottom(true);
        }
    },
);

onMounted(() => {
    scrollToBottom();
});

defineExpose({ scrollToBottom });
</script>

<template>
    <div
        ref="containerRef"
        class="flex-1 overflow-y-auto px-4 py-4"
        @scroll="onScroll"
    >
        <div v-if="loadingMore" class="flex justify-center py-3">
            <Spinner class="size-5" />
        </div>

        <template v-for="group in groupedMessages" :key="group.date">
            <div class="my-5 flex justify-center">
                <span class="bg-muted/80 text-muted-foreground rounded-full px-3 py-1 text-[11px] font-medium shadow-sm">
                    {{ formatDateLabel(group.messages[0].created_at) }}
                </span>
            </div>

            <div class="space-y-1.5">
                <MessageBubble
                    v-for="msg in group.messages"
                    :key="msg.id"
                    :message="msg"
                    :is-mine="msg.sender_id === authUserId"
                    :is-read="msg.sender_id === authUserId && msg.id <= (readByOthersUpToId ?? 0)"
                    :show-sender="showSenderNames"
                    :auth-user-id="authUserId"
                    @delete-for-me="emit('deleteForMe', $event)"
                    @delete-for-everyone="emit('deleteForEveryone', $event)"
                    @react="(id, emoji) => emit('react', id, emoji)"
                    @forward="emit('forward', $event)"
                />
            </div>
        </template>

        <div
            v-if="messages.length === 0"
            class="flex h-full flex-col items-center justify-center gap-1"
        >
            <p class="text-muted-foreground text-sm">No messages yet</p>
            <p class="text-muted-foreground/60 text-xs">Send a message to start the conversation</p>
        </div>
    </div>
</template>
