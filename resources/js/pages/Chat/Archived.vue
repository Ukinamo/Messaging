<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import ChatAvatar from '@/components/chat/ChatAvatar.vue';
import type { ArchivedConversationEntry } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArchiveRestore, Archive } from 'lucide-vue-next';
import axios from 'axios';
import { computed, ref } from 'vue';
import { index as chatIndex } from '@/routes/chat';

const props = defineProps<{
    archivedConversations: ArchivedConversationEntry[];
}>();

const breadcrumbs = [
    { title: 'Chat', href: '/chat' },
    { title: 'Archived', href: '/chat/archived' },
];

const items = ref([...props.archivedConversations]);
const restoringId = ref<number | null>(null);
const searchQuery = ref('');

const filtered = computed(() => {
    if (!searchQuery.value.trim()) return items.value;
    const q = searchQuery.value.toLowerCase();
    return items.value.filter(
        (c) =>
            (c.name || '').toLowerCase().includes(q) ||
            (c.latest_message?.body || '').toLowerCase().includes(q),
    );
});

async function handleRestore(conversationId: number) {
    restoringId.value = conversationId;
    try {
        await axios.delete(`/api/chat/conversations/${conversationId}/archive`);
        items.value = items.value.filter((c) => c.id !== conversationId);
    } finally {
        restoringId.value = null;
    }
}

function openConversation(conversationId: number) {
    router.visit(`/chat/${conversationId}`);
}
</script>

<template>
    <Head title="Archived Conversations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="archive-page-z mx-auto px-4 py-8">
            <div class="archive-header-z">
                <div class="header-title">
                    <h2>Archived Conversations</h2>
                    <p class="header-subtitle">
                        {{ items.length }} archived conversation{{
                            items.length !== 1 ? 's' : ''
                        }}
                    </p>
                </div>

                <div class="search-wrapper-z">
                    <svg
                        class="search-icon ml-1 text-[var(--muted-text)]"
                        width="18"
                        height="18"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                    <input
                        v-model="searchQuery"
                        type="text"
                        class="search-input-z"
                        placeholder="Search archived..."
                    />
                </div>
            </div>

            <div v-if="filtered.length === 0 && items.length === 0" class="archive-empty-z">
                <div class="text-[var(--accent-lavender)] opacity-50">
                    <Archive class="size-20 stroke-1" />
                </div>
                <h3 class="text-xl font-bold text-foreground">
                    No archived conversations
                </h3>
                <p class="text-muted-foreground text-sm">
                    Archived conversations will appear here
                </p>
                <Link :href="chatIndex.url()" class="btn btn-primary mt-2">
                    View Messages
                </Link>
            </div>

            <div
                v-else-if="filtered.length === 0"
                class="archive-empty-z"
            >
                <p class="text-muted-foreground text-sm">No matches</p>
            </div>

            <div v-else class="archive-list-z">
                <div
                    v-for="conv in filtered"
                    :key="conv.id"
                    class="archive-item-z"
                >
                    <button
                        type="button"
                        class="flex min-w-0 flex-1 items-center gap-3 text-left"
                        @click="openConversation(conv.id)"
                    >
                        <ChatAvatar :name="conv.name || 'Chat'" :avatar="conv.avatar" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-foreground">
                                {{ conv.name || 'Chat' }}
                            </p>
                            <p
                                v-if="conv.latest_message"
                                class="text-muted-foreground truncate text-xs"
                            >
                                {{ conv.latest_message.body || 'Attachment' }}
                            </p>
                        </div>
                    </button>
                    <button
                        type="button"
                        class="text-muted-foreground hover:bg-muted/80 hover:text-foreground shrink-0 rounded-md p-2 transition-colors"
                        :disabled="restoringId === conv.id"
                        title="Restore"
                        @click.stop="handleRestore(conv.id)"
                    >
                        <ArchiveRestore class="size-5" />
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
