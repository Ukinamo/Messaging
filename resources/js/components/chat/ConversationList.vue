<script setup lang="ts">
import { Input } from '@/components/ui/input';
import type { ConversationSummary } from '@/types';
import { MessageSquarePlus, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConversationItem from './ConversationItem.vue';

const props = defineProps<{
    conversations: ConversationSummary[];
    activeConversationId?: number;
    onlineUserIds: Set<number>;
    authUserId: number;
}>();

const emit = defineEmits<{
    select: [conversation: ConversationSummary];
    newChat: [];
}>();

const search = ref('');

const filtered = computed(() => {
    if (!search.value.trim()) return props.conversations;
    const q = search.value.toLowerCase();
    return props.conversations.filter((c) => c.name?.toLowerCase().includes(q));
});

function isOnline(conv: ConversationSummary): boolean {
    return conv.participants.some(
        (p) => p.id !== props.authUserId && props.onlineUserIds.has(p.id),
    );
}
</script>

<template>
    <div class="flex h-full flex-col">
        <div class="flex items-center justify-between border-b px-4 py-3">
            <h2 class="text-lg font-semibold">Messages</h2>
            <button
                class="text-muted-foreground hover:text-foreground rounded-md p-1.5 transition-colors hover:bg-muted"
                title="New conversation"
                @click="emit('newChat')"
            >
                <MessageSquarePlus class="size-5" />
            </button>
        </div>

        <div class="px-3 py-2">
            <div class="relative">
                <Search class="text-muted-foreground absolute top-1/2 left-2.5 size-4 -translate-y-1/2" />
                <Input
                    v-model="search"
                    placeholder="Search conversations..."
                    class="h-8 pl-8 text-sm"
                />
            </div>
        </div>

        <div class="flex-1 space-y-0.5 overflow-y-auto px-2 pb-2">
            <ConversationItem
                v-for="conv in filtered"
                :key="conv.id"
                :conversation="conv"
                :active="conv.id === activeConversationId"
                :online="isOnline(conv)"
                @select="emit('select', conv)"
            />

            <div
                v-if="filtered.length === 0"
                class="text-muted-foreground flex flex-col items-center justify-center py-12 text-center text-sm"
            >
                <p v-if="search.trim()">No conversations found</p>
                <template v-else>
                    <p>No conversations yet</p>
                    <button
                        class="text-primary mt-1 hover:underline"
                        @click="emit('newChat')"
                    >
                        Start a new chat
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>
