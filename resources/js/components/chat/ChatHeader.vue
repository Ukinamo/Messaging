<script setup lang="ts">
import type { ActiveConversation } from '@/types';
import { ArrowLeft, Phone } from 'lucide-vue-next';
import ChatAvatar from './ChatAvatar.vue';

const props = defineProps<{
    conversation: ActiveConversation;
    online: boolean;
    typingNames: string[];
}>();

defineEmits<{
    back: [];
    openProfile: [];
    call: [];
}>();
</script>

<template>
    <div class="bg-background/80 flex items-center gap-3 border-b px-4 py-2.5 backdrop-blur-sm">
        <button
            class="text-muted-foreground hover:text-foreground hover:bg-accent -ml-1 flex size-8 items-center justify-center rounded-full transition-colors md:hidden"
            @click="$emit('back')"
        >
            <ArrowLeft class="size-5" />
        </button>

        <button
            class="flex min-w-0 flex-1 items-center gap-3 text-left"
            @click="$emit('openProfile')"
        >
            <ChatAvatar
                :name="conversation.name || 'Chat'"
                :avatar="conversation.avatar"
                :online="online"
                show-status
            />

            <div class="min-w-0 flex-1">
                <h3 class="truncate text-[13px] font-semibold leading-tight">{{ conversation.name || 'Chat' }}</h3>
                <p v-if="typingNames.length > 0" class="text-primary mt-0.5 text-xs font-medium">
                    <template v-if="typingNames.length === 1">{{ typingNames[0] }} is typing...</template>
                    <template v-else>{{ typingNames.length }} people are typing...</template>
                </p>
                <p v-else class="mt-0.5 text-xs">
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
        </button>

        <button
            v-if="conversation.type === 'private'"
            class="text-muted-foreground hover:text-foreground hover:bg-accent flex size-9 items-center justify-center rounded-full transition-colors"
            title="Voice call"
            @click="$emit('call')"
        >
            <Phone class="size-[18px]" />
        </button>
    </div>
</template>
