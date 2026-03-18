<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type { ConversationSummary } from '@/types';
import { Forward, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ChatAvatar from './ChatAvatar.vue';

const props = defineProps<{
    open: boolean;
    conversations: ConversationSummary[];
    activeConversationId?: number;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    select: [conversationId: number];
}>();

const search = ref('');

watch(
    () => props.open,
    (val) => {
        if (val) search.value = '';
    },
);

const filtered = computed(() => {
    const list = props.conversations.filter((c) => c.id !== props.activeConversationId);
    if (!search.value.trim()) return list;
    const q = search.value.toLowerCase();
    return list.filter((c) => c.name?.toLowerCase().includes(q));
});

function handleSelect(conversationId: number) {
    emit('select', conversationId);
    emit('update:open', false);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Forward class="size-5" />
                    Forward message
                </DialogTitle>
                <DialogDescription>
                    Choose a conversation to forward this message to.
                </DialogDescription>
            </DialogHeader>

            <div class="relative">
                <Search class="text-muted-foreground absolute top-1/2 left-2.5 size-4 -translate-y-1/2" />
                <Input
                    v-model="search"
                    placeholder="Search conversations..."
                    class="pl-8"
                />
            </div>

            <div class="max-h-64 space-y-1 overflow-y-auto">
                <button
                    v-for="conv in filtered"
                    :key="conv.id"
                    class="hover:bg-muted flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition-colors"
                    @click="handleSelect(conv.id)"
                >
                    <ChatAvatar
                        :name="conv.name || 'Chat'"
                        :avatar="conv.avatar"
                        :online="false"
                        size="sm"
                    />
                    <span class="truncate text-sm font-medium">
                        {{ conv.name || 'Chat' }}
                    </span>
                </button>

                <div
                    v-if="filtered.length === 0"
                    class="text-muted-foreground py-6 text-center text-sm"
                >
                    No conversations found
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
