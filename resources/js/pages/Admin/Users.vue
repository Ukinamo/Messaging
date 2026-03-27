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

type Props = {
    users: ManagedUser[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User Management',
        href: '/admin/users',
    },
];

function selectUser(userId: number) {
    router.get('/admin/users/peek', { user_id: userId });
}

function goToCreateUser() {
    router.visit('/admin/users/create');
}

function toggleAdmin(userId: number) {
    router.post(`/admin/users/${userId}/toggle-admin`, {}, { preserveScroll: true });
}

function editUser(userId: number) {
    router.visit(`/admin/users/${userId}/edit`);
}

function deleteUser(userId: number, name: string) {
    if (!window.confirm(`Delete user "${name}"? This cannot be undone.`)) {
        return;
    }

    router.delete(`/admin/users/${userId}`, { preserveScroll: true });
}

</script>

<template>
    <Head title="User Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="rounded-xl border p-4">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <h2 class="text-lg font-semibold">Users</h2>
                    <button
                        type="button"
                        class="rounded-md border px-3 py-1 text-sm hover:bg-muted"
                        @click="goToCreateUser"
                    >
                        Create user
                    </button>
                </div>
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
                                            @click="editUser(u.id)"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-md border px-3 py-1 hover:bg-muted"
                                            @click="toggleAdmin(u.id)"
                                        >
                                            {{ u.is_admin ? 'Remove admin' : 'Make admin' }}
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-md border px-3 py-1 text-red-600 hover:bg-red-50 dark:hover:bg-red-950"
                                            @click="deleteUser(u.id, u.name)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

