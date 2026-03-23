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
        type="button"
        :class="
            cn(
                'zguide-conversation-card group flex items-center gap-2',
                props.active && 'active',
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
            class="shrink-0"
        />

        <div class="min-w-0 flex-1">
            <div class="flex items-baseline justify-between gap-2">
                <span
                    class="truncate text-[13px] font-semibold leading-tight text-foreground"
                >
                    {{ conversation.name || 'Chat' }}
                </span>
                <span
                    v-if="conversation.latest_message"
                    :class="
                        cn(
                            'shrink-0 text-[10px] tabular-nums',
                            conversation.unread_count > 0
                                ? 'font-medium text-[var(--primary-purple)]'
                                : 'text-muted-foreground',
                        )
                    "
                >
                    {{ formatTime(conversation.latest_message.created_at) }}
                </span>
            </div>
            <div class="mt-0.5 flex items-center justify-between gap-2">
                <p
                    :class="
                        cn(
                            'truncate text-xs leading-snug',
                            conversation.unread_count > 0
                                ? 'font-medium text-foreground/80'
                                : 'text-muted-foreground',
                        )
                    "
                >
                    <span
                        v-if="
                            conversation.type === 'group' &&
                            conversation.latest_message?.sender_name
                        "
                    >
                        {{ conversation.latest_message.sender_name }}:
                    </span>
                    {{ previewText(conversation) }}
                </p>
                <span
                    v-if="conversation.unread_count > 0"
                    class="flex h-[18px] min-w-[18px] shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary to-secondary px-1 text-[10px] font-bold text-primary-foreground"
                >
                    {{
                        conversation.unread_count > 99
                            ? '99+'
                            : conversation.unread_count
                    }}
                </span>
            </div>
        </div>
    </button>
</template>
