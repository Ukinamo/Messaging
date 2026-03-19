<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { ActiveConversation, ChatParticipant } from '@/types';
import axios from 'axios';
import { Archive, ArchiveRestore, Calendar, Loader2, Mail, ShieldBan, ShieldCheck, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ChatAvatar from './ChatAvatar.vue';

type UserProfile = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    created_at: string;
    is_blocked: boolean;
    is_blocked_by_them: boolean;
};

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
const profile = ref<UserProfile | null>(null);
const loadingProfile = ref(false);

const isSelfChat = computed(() => {
    if (props.conversation.type !== 'private') return false;
    return props.conversation.participants.length === 1
        && props.conversation.participants[0].id === props.authUserId;
});

const otherUser = computed<ChatParticipant | null>(() => {
    if (props.conversation.type !== 'private') return null;
    if (isSelfChat.value) return props.conversation.participants[0];
    return props.conversation.participants.find((p) => p.id !== props.authUserId) ?? null;
});

const displayName = computed(() => {
    if (profile.value) return profile.value.name;
    return props.conversation.name || 'Chat';
});

async function fetchProfile(userId: number) {
    loadingProfile.value = true;
    try {
        const { data } = await axios.get<UserProfile>(`/api/chat/users/${userId}/profile`);
        profile.value = data;
    } catch {
        profile.value = null;
    } finally {
        loadingProfile.value = false;
    }
}

watch(
    () => otherUser.value?.id,
    (id) => {
        if (id) fetchProfile(id);
        else profile.value = null;
    },
    { immediate: true },
);

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

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
        <div class="flex items-center justify-between px-4 py-3">
            <h3 class="truncate text-sm font-bold tracking-tight">{{ displayName }}</h3>
            <button
                class="text-muted-foreground hover:text-foreground hover:bg-accent flex size-7 shrink-0 items-center justify-center rounded-full transition-colors"
                @click="emit('close')"
            >
                <X class="size-4" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            <!-- Avatar & name -->
            <div class="flex flex-col items-center gap-4 px-6 pt-4 pb-6">
                <ChatAvatar
                    :name="displayName"
                    :avatar="profile?.avatar ?? conversation.avatar"
                    :online="false"
                    size="lg"
                />
                <div class="text-center">
                    <h4 class="text-base font-semibold">{{ displayName }}</h4>
                    <p v-if="conversation.type === 'group'" class="text-muted-foreground mt-0.5 text-xs">
                        {{ conversation.participants.length }} members
                    </p>
                </div>
            </div>

            <!-- Loading state -->
            <div v-if="loadingProfile && conversation.type === 'private'" class="flex justify-center py-4">
                <Loader2 class="text-muted-foreground size-5 animate-spin" />
            </div>

            <!-- Profile details (private chats) -->
            <template v-if="profile && conversation.type === 'private'">
                <div class="bg-muted/30 mx-4 space-y-0.5 rounded-xl p-1">
                    <div class="flex items-center gap-3 rounded-lg px-3 py-2.5">
                        <div class="bg-background flex size-8 items-center justify-center rounded-full shadow-sm">
                            <Mail class="text-muted-foreground size-3.5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-muted-foreground text-[10px] uppercase tracking-wider">Email</p>
                            <p class="truncate text-sm">{{ profile.email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 rounded-lg px-3 py-2.5">
                        <div class="bg-background flex size-8 items-center justify-center rounded-full shadow-sm">
                            <Calendar class="text-muted-foreground size-3.5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-muted-foreground text-[10px] uppercase tracking-wider">Member since</p>
                            <p class="text-sm">{{ formatDate(profile.created_at) }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="profile.is_blocked_by_them && !isBlocked" class="px-6 py-3">
                    <p class="text-muted-foreground text-xs italic">
                        You cannot send messages to this user.
                    </p>
                </div>
            </template>

            <!-- Actions -->
            <div class="mt-4 space-y-0.5 px-4">
                <p class="text-muted-foreground mb-1.5 px-1 text-[10px] font-medium uppercase tracking-wider">Actions</p>
                <button
                    :class="
                        cn(
                            'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors',
                            'hover:bg-muted text-foreground',
                        )
                    "
                    @click="isArchived ? emit('unarchive') : emit('archive')"
                >
                    <ArchiveRestore v-if="isArchived" class="size-4" />
                    <Archive v-else class="size-4" />
                    {{ isArchived ? 'Unarchive conversation' : 'Archive conversation' }}
                </button>

                <template v-if="conversation.type === 'private' && otherUser && !isSelfChat">
                    <button
                        :class="
                            cn(
                                'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors',
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

                    <div
                        v-if="confirmingBlock"
                        class="bg-destructive/5 border-destructive/20 rounded-xl border p-3"
                    >
                        <p class="text-xs">
                            Block <strong>{{ otherUser.name }}</strong>? They won't be able to send you messages.
                        </p>
                        <div class="mt-2.5 flex gap-2">
                            <button
                                class="bg-destructive text-destructive-foreground hover:bg-destructive/90 rounded-lg px-3 py-1.5 text-xs font-medium"
                                @click="confirmBlock"
                            >
                                Block
                            </button>
                            <button
                                class="hover:bg-muted rounded-lg px-3 py-1.5 text-xs font-medium"
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
                <div class="mt-6 px-4">
                    <p class="text-muted-foreground mb-2 px-1 text-[10px] font-medium uppercase tracking-wider">
                        Members
                    </p>
                    <div class="space-y-0.5">
                        <div
                            v-for="participant in conversation.participants"
                            :key="participant.id"
                            class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left"
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
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
