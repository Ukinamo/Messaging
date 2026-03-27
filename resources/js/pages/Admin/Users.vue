<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { ref } from 'vue';

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
const editUserId = ref<number | null>(null);
const editForm = ref({
    name: '',
    email: '',
    password: '',
    is_admin: false,
});

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

function startEdit(user: ManagedUser) {
    editUserId.value = user.id;
    editForm.value.name = user.name;
    editForm.value.email = user.email;
    editForm.value.password = '';
    editForm.value.is_admin = user.is_admin;
}

function cancelEdit() {
    editUserId.value = null;
}

function updateUser(userId: number) {
    router.put(`/admin/users/${userId}`, editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            editUserId.value = null;
        },
    });
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
                                <td class="py-3 pr-4">
                                    <template v-if="editUserId === u.id">
                                        <input v-model="editForm.name" type="text" class="w-full rounded-md border bg-background px-3 py-2" />
                                    </template>
                                    <template v-else>
                                        {{ u.name }}
                                    </template>
                                </td>
                                <td class="py-3 pr-4">
                                    <template v-if="editUserId === u.id">
                                        <div class="space-y-2">
                                            <input v-model="editForm.email" type="email" class="w-full rounded-md border bg-background px-3 py-2" />
                                            <input
                                                v-model="editForm.password"
                                                type="password"
                                                class="w-full rounded-md border bg-background px-3 py-2"
                                                placeholder="New password (optional)"
                                            />
                                        </div>
                                    </template>
                                    <template v-else>
                                        {{ u.email }}
                                    </template>
                                </td>
                                <td class="py-3 pr-4">
                                    <template v-if="editUserId === u.id">
                                        <label class="inline-flex items-center gap-2 text-sm">
                                            <input v-model="editForm.is_admin" type="checkbox" />
                                            Admin
                                        </label>
                                    </template>
                                    <template v-else>
                                        <span class="rounded-md px-2 py-1 text-xs" :class="u.is_admin ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground'">
                                            {{ u.is_admin ? 'Admin' : 'User' }}
                                        </span>
                                    </template>
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
                                        <template v-if="editUserId === u.id">
                                            <button
                                                type="button"
                                                class="rounded-md border px-3 py-1 hover:bg-muted"
                                                @click="updateUser(u.id)"
                                            >
                                                Save
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-md border px-3 py-1 hover:bg-muted"
                                                @click="cancelEdit"
                                            >
                                                Cancel
                                            </button>
                                        </template>
                                        <template v-else>
                                            <button
                                                type="button"
                                                class="rounded-md border px-3 py-1 hover:bg-muted"
                                                @click="startEdit(u)"
                                            >
                                                Edit
                                            </button>
                                        </template>
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

