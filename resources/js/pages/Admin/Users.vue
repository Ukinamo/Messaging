<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
    created_at: string;
};

type PeekConversation = {
    id: number;
    type: 'private' | 'group';
    name: string;
    latest_message: null | {
        body: string | null;
        sender_name: string;
        created_at: string;
    };
};

type Props = {
    users: ManagedUser[];
    selectedUserId?: number;
    selectedUserName?: string;
    selectedUserConversations: PeekConversation[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User Management',
        href: '/admin/users',
    },
];

function selectUser(userId: number) {
    router.get('/admin/users', { user_id: userId }, { preserveState: true, preserveScroll: true });
}

function toggleAdmin(userId: number) {
    router.post(`/admin/users/${userId}/toggle-admin`, {}, { preserveScroll: true });
}

function peekConversation(conversationId: number) {
    if (!props.selectedUserId) {
        return;
    }

    router.visit(`/chat/${conversationId}?peek_user_id=${props.selectedUserId}`);
}
</script>

<template>
    <Head title="User Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="rounded-xl border p-4">
                <h2 class="mb-3 text-lg font-semibold">Users</h2>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px] text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="py-2 pr-4">Name</th>
                                <th class="py-2 pr-4">Email</th>
                                <th class="py-2 pr-4">Role</th>
                                <th class="py-2 pr-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="u in props.users"
                                :key="u.id"
                                class="border-b last:border-b-0"
                            >
                                <td class="py-3 pr-4">{{ u.name }}</td>
                                <td class="py-3 pr-4">{{ u.email }}</td>
                                <td class="py-3 pr-4">
                                    <span class="rounded-md px-2 py-1 text-xs" :class="u.is_admin ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground'">
                                        {{ u.is_admin ? 'Admin' : 'User' }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4">
                                    <div class="flex gap-2">
                                        <button
                                            type="button"
                                            class="rounded-md border px-3 py-1 hover:bg-muted"
                                            @click="selectUser(u.id)"
                                        >
                                            View chats
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-md border px-3 py-1 hover:bg-muted"
                                            @click="toggleAdmin(u.id)"
                                        >
                                            {{ u.is_admin ? 'Remove admin' : 'Make admin' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border p-4">
                <h2 class="mb-3 text-lg font-semibold">
                    Peek Conversations
                    <span v-if="props.selectedUserName" class="text-muted-foreground">- {{ props.selectedUserName }}</span>
                </h2>

                <div v-if="!props.selectedUserId" class="text-sm text-muted-foreground">
                    Select a user above to see their conversations.
                </div>

                <div v-else-if="props.selectedUserConversations.length === 0" class="text-sm text-muted-foreground">
                    This user has no conversations yet.
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="conversation in props.selectedUserConversations"
                        :key="conversation.id"
                        class="flex items-center justify-between rounded-lg border p-3"
                    >
                        <div>
                            <div class="font-medium">{{ conversation.name }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ conversation.type }} conversation
                                <span v-if="conversation.latest_message">
                                    - {{ conversation.latest_message.sender_name }}: {{ conversation.latest_message.body ?? 'Attachment' }}
                                </span>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="rounded-md border px-3 py-1 text-sm hover:bg-muted"
                            @click="peekConversation(conversation.id)"
                        >
                            Peek
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

