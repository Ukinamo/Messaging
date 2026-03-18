<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Mail, MessageSquare, ShieldBan, ShieldCheck } from 'lucide-vue-next';
import axios from 'axios';
import { ref } from 'vue';

const props = defineProps<{
    profileUser: {
        id: number;
        name: string;
        email: string;
        avatar: string | null;
        created_at: string;
    };
    isBlocked: boolean;
    isBlockedByThem: boolean;
    sharedConversationId: number | null;
}>();

const breadcrumbs = [
    { title: 'Chat', href: '/chat' },
    { title: props.profileUser.name, href: `/chat/user/${props.profileUser.id}` },
];

const blocked = ref(props.isBlocked);
const confirmingBlock = ref(false);

function initials(name: string): string {
    if (!name) return '?';
    return name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

async function handleBlock() {
    try {
        await axios.post(`/api/chat/block/${props.profileUser.id}`);
        blocked.value = true;
        confirmingBlock.value = false;
    } catch { /* silent */ }
}

async function handleUnblock() {
    try {
        await axios.delete(`/api/chat/block/${props.profileUser.id}`);
        blocked.value = false;
    } catch { /* silent */ }
}

async function handleSendMessage() {
    if (props.sharedConversationId) {
        router.visit(`/chat/${props.sharedConversationId}`);
    } else {
        try {
            const { data } = await axios.post('/api/chat/conversations', {
                user_id: props.profileUser.id,
            });
            router.visit(`/chat/${data.conversation_id}`);
        } catch { /* silent */ }
    }
}
</script>

<template>
    <Head :title="profileUser.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl px-4 py-8">
            <!-- Back button -->
            <button
                class="text-muted-foreground hover:text-foreground mb-6 inline-flex items-center gap-1.5 text-sm transition-colors"
                @click="$router?.back?.() ?? router.visit('/chat')"
            >
                <ArrowLeft class="size-4" />
                Back
            </button>

            <!-- Profile card -->
            <div class="bg-background overflow-hidden rounded-xl border">
                <!-- Header with avatar -->
                <div class="bg-muted/30 flex flex-col items-center gap-4 px-6 py-10">
                    <Avatar class="size-24 text-2xl">
                        <AvatarImage v-if="profileUser.avatar" :src="profileUser.avatar" :alt="profileUser.name" />
                        <AvatarFallback class="bg-primary/10 text-primary text-2xl font-semibold">
                            {{ initials(profileUser.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="text-center">
                        <h1 class="text-xl font-semibold">{{ profileUser.name }}</h1>
                    </div>
                </div>

                <!-- Details -->
                <div class="divide-y">
                    <div class="flex items-center gap-3 px-6 py-4">
                        <Mail class="text-muted-foreground size-4 shrink-0" />
                        <div>
                            <p class="text-muted-foreground text-xs">Email</p>
                            <p class="text-sm">{{ profileUser.email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 px-6 py-4">
                        <Calendar class="text-muted-foreground size-4 shrink-0" />
                        <div>
                            <p class="text-muted-foreground text-xs">Member since</p>
                            <p class="text-sm">{{ formatDate(profileUser.created_at) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap gap-2 border-t px-6 py-4">
                    <button
                        v-if="!blocked && !isBlockedByThem"
                        class="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
                        @click="handleSendMessage"
                    >
                        <MessageSquare class="size-4" />
                        Send Message
                    </button>

                    <button
                        v-if="blocked"
                        class="hover:bg-muted inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium transition-colors"
                        @click="handleUnblock"
                    >
                        <ShieldCheck class="size-4" />
                        Unblock
                    </button>

                    <button
                        v-else
                        class="text-destructive hover:bg-destructive/10 inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium transition-colors"
                        @click="confirmingBlock = true"
                    >
                        <ShieldBan class="size-4" />
                        Block
                    </button>
                </div>

                <!-- Block confirmation -->
                <div v-if="confirmingBlock" class="border-t px-6 py-4">
                    <div class="bg-destructive/5 border-destructive/20 rounded-lg border p-4">
                        <p class="text-sm">
                            Block <strong>{{ profileUser.name }}</strong>? They won't be able to send you messages.
                        </p>
                        <div class="mt-3 flex gap-2">
                            <button
                                class="bg-destructive text-destructive-foreground hover:bg-destructive/90 rounded-md px-4 py-2 text-sm font-medium"
                                @click="handleBlock"
                            >
                                Block
                            </button>
                            <button
                                class="hover:bg-muted rounded-md px-4 py-2 text-sm font-medium"
                                @click="confirmingBlock = false"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Blocked by them notice -->
                <div v-if="isBlockedByThem && !blocked" class="border-t px-6 py-4">
                    <p class="text-muted-foreground text-sm italic">
                        You cannot send messages to this user.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
