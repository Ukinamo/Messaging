<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    is_admin: boolean;
};

type Props = {
    user: ManagedUser;
};

const props = defineProps<Props>();
const page = usePage<{ errors?: Record<string, string> }>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User Management',
        href: '/admin/users',
    },
    {
        title: 'Edit User',
        href: `/admin/users/${props.user.id}/edit`,
    },
];

const form = reactive({
    name: props.user.name,
    email: props.user.email,
    password: '',
    is_admin: props.user.is_admin,
});

function updateUser() {
    router.put(`/admin/users/${props.user.id}`, form, {
        preserveScroll: true,
        onSuccess: () => {
            form.password = '';
        },
    });
}
</script>

<template>
    <Head :title="`Edit User - ${props.user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="rounded-xl border p-4">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <h2 class="text-lg font-semibold">Edit user</h2>
                    <button
                        type="button"
                        class="rounded-md border px-3 py-1 text-sm hover:bg-muted"
                        @click="router.visit('/admin/users')"
                    >
                        Back to users
                    </button>
                </div>

                <form class="grid gap-3 md:grid-cols-2" @submit.prevent="updateUser">
                    <div>
                        <label class="mb-1 block text-xs text-muted-foreground">Name</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-md border bg-background px-3 py-2"
                        />
                        <p v-if="page.props.errors?.name" class="mt-1 text-xs text-red-500">
                            {{ page.props.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs text-muted-foreground">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full rounded-md border bg-background px-3 py-2"
                        />
                        <p v-if="page.props.errors?.email" class="mt-1 text-xs text-red-500">
                            {{ page.props.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs text-muted-foreground">New password (optional)</label>
                        <input
                            v-model="form.password"
                            type="password"
                            class="w-full rounded-md border bg-background px-3 py-2"
                            placeholder="Leave empty to keep current password"
                        />
                        <p v-if="page.props.errors?.password" class="mt-1 text-xs text-red-500">
                            {{ page.props.errors.password }}
                        </p>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.is_admin" type="checkbox" />
                        Admin user
                    </label>

                    <div class="md:col-span-2">
                        <button
                            type="submit"
                            class="rounded-md border px-3 py-2 hover:bg-muted"
                        >
                            Save changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
