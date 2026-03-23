<script setup lang="ts">
import ChatAvatar from '@/components/chat/ChatAvatar.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, ConversationSummary } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import {
    archived as chatArchived,
    blocked as chatBlocked,
    index as chatIndex,
    show as chatShowRoute,
} from '@/routes/chat';
import {
    Archive,
    Ban,
    Inbox,
    MessageCircle,
    Plus,
    Search,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    stats: {
        totalConversations: number;
        unreadMessages: number;
        archivedCount: number;
        blockedCount: number;
    };
    recentConversations: ConversationSummary[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
];

const overviewCards = computed(() => [
    {
        id: 1,
        label: 'Total Conversations',
        value: String(props.stats.totalConversations),
        icon: MessageCircle,
        gradient:
            'linear-gradient(135deg, rgba(28, 26, 133, 0.1) 0%, rgba(100, 39, 207, 0.1) 100%)',
    },
    {
        id: 2,
        label: 'Unread Messages',
        value: String(props.stats.unreadMessages),
        icon: Inbox,
        gradient:
            'linear-gradient(135deg, rgba(100, 39, 207, 0.1) 0%, rgba(165, 139, 215, 0.1) 100%)',
    },
    {
        id: 3,
        label: 'Archived',
        value: String(props.stats.archivedCount),
        icon: Archive,
        gradient:
            'linear-gradient(135deg, rgba(28, 26, 133, 0.1) 0%, rgba(100, 39, 207, 0.1) 100%)',
    },
    {
        id: 4,
        label: 'Blocked Users',
        value: String(props.stats.blockedCount),
        icon: Ban,
        gradient:
            'linear-gradient(135deg, rgba(107, 107, 127, 0.1) 0%, rgba(156, 163, 175, 0.1) 100%)',
    },
]);

function formatTime(dateStr: string): string {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const days = Math.floor(diff / 86400000);
    const mins = Math.floor(diff / 60000);

    if (mins < 1) return 'Just now';
    if (mins < 60) return `${mins} min`;
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

function openConversation(conv: ConversationSummary) {
    router.visit(chatShowRoute.url(conv.id));
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="dashboard-page">
            <!-- Overview Cards -->
            <div class="overview-grid">
                <div
                    v-for="card in overviewCards"
                    :key="card.id"
                    class="overview-card"
                >
                    <div
                        class="card-icon"
                        :style="{ background: card.gradient }"
                    >
                        <component :is="card.icon" class="h-7 w-7" stroke-width="2" />
                    </div>
                    <div class="card-content">
                        <p class="card-label">{{ card.label }}</p>
                        <h3 class="card-value">{{ card.value }}</h3>
                    </div>
                </div>
            </div>

            <!-- Recent Conversations -->
            <section class="recent-section">
                <div class="section-header">
                    <h2>Recent Conversations</h2>
                    <Link :href="chatIndex.url()" class="view-all-link"
                        >View All</Link
                    >
                </div>

                <div
                    v-if="recentConversations.length === 0"
                    class="dashboard-empty-recent"
                >
                    <p class="text-muted-foreground text-sm">
                        No conversations yet. Start a new chat from Messages.
                    </p>
                    <Link :href="chatIndex.url()" class="btn btn-primary mt-3">
                        Go to Messages
                    </Link>
                </div>

                <div v-else class="conversations-list">
                    <button
                        v-for="conv in recentConversations"
                        :key="conv.id"
                        type="button"
                        class="conversation-item w-full text-left"
                        @click="openConversation(conv)"
                    >
                        <div class="conversation-avatar-wrapper">
                            <ChatAvatar
                                :name="conv.name || 'Chat'"
                                :avatar="conv.avatar"
                                size="md"
                                class="shrink-0"
                            />
                        </div>

                        <div class="conversation-info">
                            <div class="conversation-header">
                                <h4 class="conversation-name">
                                    {{ conv.name || 'Chat' }}
                                </h4>
                                <span
                                    v-if="conv.latest_message"
                                    class="conversation-time"
                                >
                                    {{
                                        formatTime(
                                            conv.latest_message.created_at,
                                        )
                                    }}
                                </span>
                            </div>
                            <p class="conversation-preview">
                                {{ previewText(conv) }}
                            </p>
                        </div>

                        <div
                            v-if="conv.unread_count > 0"
                            class="unread-badge"
                        >
                            {{
                                conv.unread_count > 99
                                    ? '99+'
                                    : conv.unread_count
                            }}
                        </div>
                    </button>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="quick-actions-section">
                <h2>Quick Actions</h2>
                <div class="actions-grid">
                    <Link :href="chatIndex.url()" class="action-card">
                        <div class="action-icon">
                            <Plus class="h-6 w-6" stroke-width="2" />
                        </div>
                        <span>New Conversation</span>
                    </Link>

                    <Link :href="chatIndex.url()" class="action-card">
                        <div class="action-icon">
                            <Search class="h-6 w-6" stroke-width="2" />
                        </div>
                        <span>Search Messages</span>
                    </Link>

                    <Link :href="chatArchived.url()" class="action-card">
                        <div class="action-icon">
                            <Archive class="h-6 w-6" stroke-width="2" />
                        </div>
                        <span>View Archive</span>
                    </Link>

                    <Link :href="chatBlocked.url()" class="action-card">
                        <div class="action-icon">
                            <Ban class="h-6 w-6" stroke-width="2" />
                        </div>
                        <span>Manage Blocked</span>
                    </Link>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
