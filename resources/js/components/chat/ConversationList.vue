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
        <div class="flex items-center justify-between px-4 pb-2 pt-4">
            <h2 class="text-base font-bold tracking-tight">Messages</h2>
            <button
                class="text-muted-foreground hover:text-foreground hover:bg-accent flex size-8 items-center justify-center rounded-full transition-colors"
                title="New conversation"
                @click="emit('newChat')"
            >
                <MessageSquarePlus class="size-[18px]" />
            </button>
        </div>

        <div class="px-3 pb-1 pt-1">
            <div class="relative">
                <Search class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                <Input
                    v-model="search"
                    placeholder="Search..."
                    class="bg-muted/60 h-9 rounded-lg border-none pl-9 text-sm shadow-none focus-visible:bg-muted focus-visible:ring-1"
                />
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-2 pt-1 pb-2">
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
                class="text-muted-foreground flex flex-col items-center justify-center py-16 text-center"
            >
                <template v-if="search.trim()">
                    <Search class="text-muted-foreground/50 mb-2 size-8" />
                    <p class="text-sm">No conversations found</p>
                </template>
                <template v-else>
                    <MessageSquarePlus class="text-muted-foreground/40 mb-3 size-10" />
                    <p class="text-sm font-medium">No conversations yet</p>
                    <button
                        class="text-primary mt-2 text-sm hover:underline"
                        @click="emit('newChat')"
                    >
                        Start a new chat
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>
