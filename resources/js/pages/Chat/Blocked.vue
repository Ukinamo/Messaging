<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import ChatAvatar from '@/components/chat/ChatAvatar.vue';
import type { BlockedUserEntry } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ShieldBan, ShieldCheck } from 'lucide-vue-next';
import axios from 'axios';
import { computed, ref } from 'vue';
import { index as chatIndex } from '@/routes/chat';

const props = defineProps<{
    blockedUsers: BlockedUserEntry[];
}>();

const breadcrumbs = [
    { title: 'Chat', href: '/chat' },
    { title: 'Blocked Users', href: '/chat/blocked' },
];

const items = ref([...props.blockedUsers]);
const unblockingId = ref<number | null>(null);
const searchQuery = ref('');

const filtered = computed(() => {
    if (!searchQuery.value.trim()) return items.value;
    const q = searchQuery.value.toLowerCase();
    return items.value.filter(
        (c) =>
            c.user &&
            (c.user.name.toLowerCase().includes(q) ||
                c.user.email.toLowerCase().includes(q)),
    );
});

async function handleUnblock(userId: number) {
    unblockingId.value = userId;
    try {
        await axios.delete(`/api/chat/block/${userId}`);
        items.value = items.value.filter((b) => b.user.id !== userId);
    } finally {
        unblockingId.value = null;
    }
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head title="Blocked Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="blocked-page-z mx-auto px-4 py-8">
            <div class="blocked-header-z">
                <div class="header-title">
                    <h2>Blocked Users</h2>
                    <p class="header-subtitle">
                        {{ items.length }} blocked user{{
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
                        placeholder="Search blocked users..."
                    />
                </div>
            </div>

            <div v-if="filtered.length === 0 && items.length === 0" class="blocked-empty-z">
                <div class="text-[var(--accent-lavender)] opacity-50">
                    <ShieldBan class="size-20 stroke-1" />
                </div>
                <h3 class="text-xl font-bold text-foreground">
                    No blocked users
                </h3>
                <p class="text-muted-foreground text-sm">
                    Users you block will appear here
                </p>
                <Link :href="chatIndex.url()" class="btn btn-primary mt-2">
                    Back to Messages
                </Link>
            </div>

            <div
                v-else-if="filtered.length === 0"
                class="blocked-empty-z"
            >
                <p class="text-muted-foreground text-sm">No matches</p>
            </div>

            <div v-else class="blocked-grid-z">
                <div
                    v-for="entry in filtered"
                    :key="entry.id"
                    class="blocked-card-z"
                >
                    <ChatAvatar
                        :name="entry.user.name"
                        :avatar="entry.user.avatar"
                        class="!mx-auto"
                    />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-foreground">
                            {{ entry.user.name }}
                        </p>
                        <p class="text-muted-foreground text-xs break-all">
                            {{ entry.user.email }}
                        </p>
                        <p class="text-muted-foreground mt-1 text-xs">
                            Blocked {{ formatDate(entry.created_at) }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="btn btn-secondary mt-1 flex w-full items-center justify-center gap-2 text-sm"
                        :disabled="unblockingId === entry.user.id"
                        @click="handleUnblock(entry.user.id)"
                    >
                        <ShieldCheck class="size-4" />
                        Unblock
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
