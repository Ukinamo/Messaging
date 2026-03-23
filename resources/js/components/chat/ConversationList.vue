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
const activeFilter = ref<'all' | 'unread'>('all');

const filtered = computed(() => {
    let list = !search.value.trim()
        ? props.conversations
        : props.conversations.filter((c) =>
              c.name?.toLowerCase().includes(search.value.toLowerCase()),
          );
    if (activeFilter.value === 'unread') {
        list = list.filter((c) => c.unread_count > 0);
    }
    return list;
});

function isOnline(conv: ConversationSummary): boolean {
    return conv.participants.some(
        (p) => p.id !== props.authUserId && props.onlineUserIds.has(p.id),
    );
}
</script>

<template>
    <div class="zguide-conversations-panel flex h-full flex-col">
        <div class="zguide-conv-header">
            <div class="flex items-center justify-between px-3 pb-1.5 pt-3 md:px-4">
                <h2 class="text-base font-bold tracking-tight text-foreground">
                    Messages
                </h2>
                <button
                    type="button"
                    class="text-muted-foreground hover:text-foreground hover:bg-accent flex size-8 items-center justify-center rounded-full transition-colors"
                    title="New conversation"
                    @click="emit('newChat')"
                >
                    <MessageSquarePlus class="size-[18px]" />
                </button>
            </div>

            <div class="px-3 pb-1 pt-0 md:px-4">
                <div class="relative">
                    <Search
                        class="text-muted-foreground pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2"
                    />
                    <Input
                        v-model="search"
                        placeholder="Search..."
                        class="h-8 rounded-md border-none bg-muted/50 pl-8 text-xs shadow-none focus-visible:bg-muted focus-visible:ring-1"
                    />
                </div>
            </div>

            <div class="zguide-filter-tabs px-3 pb-2 md:px-4">
                <button
                    type="button"
                    class="zguide-filter-tab"
                    :class="{ active: activeFilter === 'all' }"
                    @click="activeFilter = 'all'"
                >
                    All
                </button>
                <button
                    type="button"
                    class="zguide-filter-tab"
                    :class="{ active: activeFilter === 'unread' }"
                    @click="activeFilter = 'unread'"
                >
                    Unread
                </button>
            </div>
        </div>

        <div class="conversations-list-scroll px-1 pb-2 md:px-2">
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
                class="text-muted-foreground flex flex-col items-center justify-center px-4 py-16 text-center"
            >
                <template v-if="search.trim() || activeFilter === 'unread'">
                    <Search class="text-muted-foreground/50 mb-2 size-8" />
                    <p class="text-sm">No conversations match</p>
                </template>
                <template v-else>
                    <MessageSquarePlus class="text-muted-foreground/40 mb-3 size-10" />
                    <p class="text-sm font-medium">No conversations yet</p>
                    <button
                        type="button"
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
