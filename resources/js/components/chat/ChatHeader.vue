<script setup lang="ts">
import type { ActiveConversation } from '@/types';
import { ArrowLeft, Phone, Video } from 'lucide-vue-next';
import ChatAvatar from './ChatAvatar.vue';

const props = defineProps<{
    conversation: ActiveConversation;
    online: boolean;
    typingNames: string[];
}>();

defineEmits<{
    back: [];
}>();
</script>

<template>
    <div class="flex items-center gap-3 border-b px-4 py-3">
        <button
            class="text-muted-foreground hover:text-foreground -ml-1 rounded-md p-1 transition-colors md:hidden"
            @click="$emit('back')"
        >
            <ArrowLeft class="size-5" />
        </button>

        <ChatAvatar
            :name="conversation.name || 'Chat'"
            :avatar="conversation.avatar"
            :online="online"
            show-status
        />

        <div class="min-w-0 flex-1">
            <h3 class="truncate text-sm font-semibold">{{ conversation.name || 'Chat' }}</h3>
            <p v-if="typingNames.length > 0" class="text-primary animate-pulse text-xs">
                <template v-if="typingNames.length === 1">{{ typingNames[0] }} is typing...</template>
                <template v-else>{{ typingNames.length }} people are typing...</template>
            </p>
            <p v-else class="text-xs">
                <span v-if="online" class="inline-flex items-center gap-1 text-emerald-500">
                    <span class="inline-block size-1.5 rounded-full bg-emerald-500" />
                    Online
                </span>
                <span v-else class="text-muted-foreground">Offline</span>
                <template v-if="conversation.type === 'group'">
                    <span class="text-muted-foreground"> &middot; {{ conversation.participants.length }} members</span>
                </template>
            </p>
        </div>

        <div class="flex items-center gap-1">
            <button class="text-muted-foreground hover:text-foreground rounded-md p-2 transition-colors hover:bg-muted">
                <Phone class="size-4" />
            </button>
            <button class="text-muted-foreground hover:text-foreground rounded-md p-2 transition-colors hover:bg-muted">
                <Video class="size-4" />
            </button>
        </div>
    </div>
</template>
