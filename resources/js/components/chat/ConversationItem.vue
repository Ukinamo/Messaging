<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { ConversationSummary } from '@/types';
import ChatAvatar from './ChatAvatar.vue';

const props = defineProps<{
    conversation: ConversationSummary;
    active: boolean;
    online: boolean;
}>();

defineEmits<{
    select: [conversation: ConversationSummary];
}>();

function formatTime(dateStr: string): string {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const days = Math.floor(diff / 86400000);

    if (days === 0) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    if (days === 1) return 'Yesterday';
    if (days < 7) return date.toLocaleDateString([], { weekday: 'short' });
    return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
}

function previewText(conv: ConversationSummary): string {
    if (!conv.latest_message) return 'No messages yet';
    const msg = conv.latest_message;
    if (msg.type === 'image') return '📷 Photo';
    if (msg.type === 'video') return '🎥 Video';
    if (msg.type === 'file') return '📎 File';
    if (msg.type === 'audio') return '🎵 Audio';
    return msg.body || '';
}
</script>

<template>
    <button
        :class="
            cn(
                'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-colors',
                active
                    ? 'bg-accent text-accent-foreground'
                    : 'hover:bg-muted/50',
            )
        "
        @click="$emit('select', conversation)"
    >
        <ChatAvatar
            :name="conversation.name || 'Chat'"
            :avatar="conversation.avatar"
            :online="online"
            show-status
            size="md"
        />

        <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2">
                <span class="truncate text-sm font-medium">
                    {{ conversation.name || 'Chat' }}
                </span>
                <span
                    v-if="conversation.latest_message"
                    class="text-muted-foreground shrink-0 text-xs"
                >
                    {{ formatTime(conversation.latest_message.created_at) }}
                </span>
            </div>
            <div class="flex items-center justify-between gap-2">
                <p class="text-muted-foreground truncate text-xs">
                    <span v-if="conversation.type === 'group' && conversation.latest_message?.sender_name">
                        {{ conversation.latest_message.sender_name }}:
                    </span>
                    {{ previewText(props.conversation) }}
                </p>
                <span
                    v-if="conversation.unread_count > 0"
                    class="bg-primary text-primary-foreground flex size-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
                >
                    {{ conversation.unread_count > 99 ? '99+' : conversation.unread_count }}
                </span>
            </div>
        </div>
    </button>
</template>
