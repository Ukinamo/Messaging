<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import ChatAvatar from '@/components/chat/ChatAvatar.vue';
import type { ArchivedConversationEntry } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ArchiveRestore, Archive } from 'lucide-vue-next';
import axios from 'axios';
import { ref } from 'vue';

const props = defineProps<{
    archivedConversations: ArchivedConversationEntry[];
}>();

const breadcrumbs = [
    { title: 'Chat', href: '/chat' },
    { title: 'Archived', href: '/chat/archived' },
];

const items = ref([...props.archivedConversations]);
const restoringId = ref<number | null>(null);

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
        <div class="mx-auto max-w-3xl px-4 py-8">
            <div class="mb-6 flex items-center gap-3">
                <div class="bg-muted flex size-10 items-center justify-center rounded-full">
                    <Archive class="text-muted-foreground size-5" />
                </div>
                <div>
                    <h1 class="text-lg font-semibold">Archived Conversations</h1>
                    <p class="text-muted-foreground text-sm">
                        {{ items.length }} archived conversation{{ items.length !== 1 ? 's' : '' }}
                    </p>
                </div>
            </div>

            <div v-if="items.length === 0" class="flex flex-col items-center justify-center gap-3 py-16">
                <div class="bg-muted flex size-16 items-center justify-center rounded-full">
                    <Archive class="text-muted-foreground size-8" />
                </div>
                <p class="text-muted-foreground text-sm">No archived conversations</p>
            </div>

            <div v-else class="space-y-1">
                <div
                    v-for="conv in items"
                    :key="conv.id"
                    class="hover:bg-muted/50 flex items-center gap-3 rounded-lg px-4 py-3 transition-colors"
                >
                    <button class="flex min-w-0 flex-1 items-center gap-3 text-left" @click="openConversation(conv.id)">
                        <ChatAvatar :name="conv.name || 'Chat'" :avatar="conv.avatar" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ conv.name || 'Chat' }}</p>
                            <p v-if="conv.latest_message" class="text-muted-foreground truncate text-xs">
                                {{ conv.latest_message.body || 'Attachment' }}
                            </p>
                        </div>
                    </button>
                    <button
                        class="text-muted-foreground hover:text-foreground hover:bg-muted shrink-0 rounded-md p-2 transition-colors"
                        :disabled="restoringId === conv.id"
                        title="Restore"
                        @click.stop="handleRestore(conv.id)"
                    >
                        <ArchiveRestore class="size-4" />
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
