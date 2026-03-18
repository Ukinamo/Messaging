<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import ChatAvatar from '@/components/chat/ChatAvatar.vue';
import type { BlockedUserEntry } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ShieldBan, ShieldCheck } from 'lucide-vue-next';
import axios from 'axios';
import { ref } from 'vue';

const props = defineProps<{
    blockedUsers: BlockedUserEntry[];
}>();

const breadcrumbs = [
    { title: 'Chat', href: '/chat' },
    { title: 'Blocked Users', href: '/chat/blocked' },
];

const items = ref([...props.blockedUsers]);
const unblockingId = ref<number | null>(null);

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
        <div class="mx-auto max-w-3xl px-4 py-8">
            <div class="mb-6 flex items-center gap-3">
                <div class="bg-muted flex size-10 items-center justify-center rounded-full">
                    <ShieldBan class="text-muted-foreground size-5" />
                </div>
                <div>
                    <h1 class="text-lg font-semibold">Blocked Users</h1>
                    <p class="text-muted-foreground text-sm">
                        {{ items.length }} blocked user{{ items.length !== 1 ? 's' : '' }}
                    </p>
                </div>
            </div>

            <div v-if="items.length === 0" class="flex flex-col items-center justify-center gap-3 py-16">
                <div class="bg-muted flex size-16 items-center justify-center rounded-full">
                    <ShieldBan class="text-muted-foreground size-8" />
                </div>
                <p class="text-muted-foreground text-sm">No blocked users</p>
            </div>

            <div v-else class="space-y-1">
                <div
                    v-for="entry in items"
                    :key="entry.id"
                    class="hover:bg-muted/50 flex items-center gap-3 rounded-lg px-4 py-3 transition-colors"
                >
                    <ChatAvatar :name="entry.user.name" :avatar="entry.user.avatar" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ entry.user.name }}</p>
                        <p class="text-muted-foreground text-xs">
                            Blocked {{ formatDate(entry.created_at) }}
                        </p>
                    </div>
                    <button
                        class="hover:bg-muted text-muted-foreground hover:text-foreground flex shrink-0 items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                        :disabled="unblockingId === entry.user.id"
                        @click="handleUnblock(entry.user.id)"
                    >
                        <ShieldCheck class="size-3.5" />
                        Unblock
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
