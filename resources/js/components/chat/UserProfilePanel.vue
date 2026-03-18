<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { ActiveConversation, ChatParticipant } from '@/types';
import { router } from '@inertiajs/vue3';
import { Archive, ArchiveRestore, ExternalLink, ShieldBan, ShieldCheck, User, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ChatAvatar from './ChatAvatar.vue';

const props = defineProps<{
    conversation: ActiveConversation;
    authUserId: number;
    isArchived: boolean;
    isBlocked: boolean;
}>();

const emit = defineEmits<{
    close: [];
    archive: [];
    unarchive: [];
    block: [userId: number];
    unblock: [userId: number];
}>();

const confirmingBlock = ref(false);

const otherUser = computed<ChatParticipant | null>(() => {
    if (props.conversation.type !== 'private') return null;
    return props.conversation.participants.find((p) => p.id !== props.authUserId) ?? null;
});

function handleBlockClick() {
    if (props.isBlocked) {
        if (otherUser.value) emit('unblock', otherUser.value.id);
    } else {
        confirmingBlock.value = true;
    }
}

function confirmBlock() {
    if (otherUser.value) emit('block', otherUser.value.id);
    confirmingBlock.value = false;
}
</script>

<template>
    <div class="bg-background flex h-full w-80 shrink-0 flex-col border-l">
        <div class="flex items-center justify-between border-b px-4 py-3">
            <h3 class="text-sm font-semibold">Profile</h3>
            <button
                class="text-muted-foreground hover:text-foreground rounded-md p-1 transition-colors"
                @click="emit('close')"
            >
                <X class="size-4" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            <div class="flex flex-col items-center gap-3 px-6 py-8">
                <ChatAvatar
                    :name="conversation.name || 'Chat'"
                    :avatar="conversation.avatar"
                    :online="false"
                    size="lg"
                />
                <div class="text-center">
                    <h4 class="text-base font-semibold">{{ conversation.name || 'Chat' }}</h4>
                    <p v-if="otherUser" class="text-muted-foreground text-xs">
                        {{ conversation.participants.find((p) => p.id !== authUserId)?.name }}
                    </p>
                    <p v-if="conversation.type === 'group'" class="text-muted-foreground text-xs">
                        {{ conversation.participants.length }} members
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="space-y-1 px-3">
                <!-- View Profile (private chats) -->
                <button
                    v-if="conversation.type === 'private' && otherUser"
                    :class="
                        cn(
                            'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors',
                            'hover:bg-muted text-foreground',
                        )
                    "
                    @click="router.visit(`/chat/user/${otherUser.id}`)"
                >
                    <User class="size-4" />
                    View profile
                </button>

                <!-- Archive / Unarchive -->
                <button
                    :class="
                        cn(
                            'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors',
                            'hover:bg-muted text-foreground',
                        )
                    "
                    @click="isArchived ? emit('unarchive') : emit('archive')"
                >
                    <ArchiveRestore v-if="isArchived" class="size-4" />
                    <Archive v-else class="size-4" />
                    {{ isArchived ? 'Unarchive conversation' : 'Archive conversation' }}
                </button>

                <!-- Block / Unblock (private only) -->
                <template v-if="conversation.type === 'private' && otherUser">
                    <button
                        :class="
                            cn(
                                'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors',
                                isBlocked
                                    ? 'hover:bg-muted text-foreground'
                                    : 'hover:bg-destructive/10 text-destructive',
                            )
                        "
                        @click="handleBlockClick"
                    >
                        <ShieldCheck v-if="isBlocked" class="size-4" />
                        <ShieldBan v-else class="size-4" />
                        {{ isBlocked ? 'Unblock user' : 'Block user' }}
                    </button>

                    <!-- Block confirmation -->
                    <div
                        v-if="confirmingBlock"
                        class="bg-destructive/5 border-destructive/20 mx-1 rounded-lg border p-3"
                    >
                        <p class="text-xs">
                            Block <strong>{{ otherUser.name }}</strong>? They won't be able to send you messages.
                        </p>
                        <div class="mt-2 flex gap-2">
                            <button
                                class="bg-destructive text-destructive-foreground hover:bg-destructive/90 rounded-md px-3 py-1.5 text-xs font-medium"
                                @click="confirmBlock"
                            >
                                Block
                            </button>
                            <button
                                class="hover:bg-muted rounded-md px-3 py-1.5 text-xs font-medium"
                                @click="confirmingBlock = false"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Participants (group chats) -->
            <template v-if="conversation.type === 'group'">
                <div class="mt-6 px-3">
                    <h5 class="text-muted-foreground mb-2 px-3 text-xs font-medium uppercase tracking-wider">
                        Members
                    </h5>
                    <div class="space-y-0.5">
                        <button
                            v-for="participant in conversation.participants"
                            :key="participant.id"
                            :class="cn(
                                'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left transition-colors',
                                participant.id !== authUserId ? 'hover:bg-muted cursor-pointer' : '',
                            )"
                            :disabled="participant.id === authUserId"
                            @click="participant.id !== authUserId && router.visit(`/chat/user/${participant.id}`)"
                        >
                            <ChatAvatar
                                :name="participant.name"
                                :avatar="participant.avatar"
                                :online="false"
                                size="sm"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    {{ participant.name }}
                                    <span v-if="participant.id === authUserId" class="text-muted-foreground text-xs">(you)</span>
                                </p>
                            </div>
                            <ExternalLink v-if="participant.id !== authUserId" class="text-muted-foreground size-3.5 shrink-0" />
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
