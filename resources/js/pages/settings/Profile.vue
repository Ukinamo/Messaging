<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { Camera, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { BreadcrumbItem } from '@/types';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
};

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit(),
    },
];

const page = usePage();
const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();

const avatarFileRef = ref<HTMLInputElement | null>(null);
const avatarPreview = ref<string | null>(null);
const avatarUploading = ref(false);
const avatarResetting = ref(false);
const avatarError = ref<string | null>(null);

function onAvatarSelect(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    if (file.size > 2 * 1024 * 1024) {
        avatarError.value = 'Image must be smaller than 2 MB.';
        input.value = '';
        return;
    }

    avatarError.value = null;
    avatarPreview.value = URL.createObjectURL(file);
    uploadAvatar(file);
    input.value = '';
}

function uploadAvatar(file: File) {
    avatarUploading.value = true;
    avatarError.value = null;

    router.post('/settings/profile/avatar', { avatar: file } as any, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            avatarPreview.value = null;
        },
        onError: (errors) => {
            avatarError.value = errors.avatar ?? 'Upload failed.';
            avatarPreview.value = null;
        },
        onFinish: () => {
            avatarUploading.value = false;
        },
    });
}

function resetAvatar() {
    avatarResetting.value = true;
    avatarError.value = null;

    router.delete('/settings/profile/avatar', {
        preserveScroll: true,
        onFinish: () => {
            avatarResetting.value = false;
            avatarPreview.value = null;
        },
    });
}

const displayAvatar = computed(() => avatarPreview.value || user.value.avatar || null);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <h1 class="sr-only">Profile settings</h1>

        <SettingsLayout>
            <!-- Avatar section -->
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Profile picture"
                    description="Upload a photo or reset to your initials"
                />

                <div class="flex items-center gap-6">
                    <div class="relative group">
                        <Avatar class="size-20 text-lg">
                            <AvatarImage v-if="displayAvatar" :src="displayAvatar" :alt="user.name" />
                            <AvatarFallback class="bg-primary/10 text-primary text-xl font-medium">
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>

                        <button
                            type="button"
                            class="absolute inset-0 flex items-center justify-center rounded-full bg-black/40 opacity-0 transition-opacity group-hover:opacity-100"
                            :disabled="avatarUploading"
                            @click="avatarFileRef?.click()"
                        >
                            <Camera class="size-6 text-white" />
                        </button>

                        <input
                            ref="avatarFileRef"
                            type="file"
                            class="hidden"
                            accept="image/jpeg,image/png,image/gif,image/webp"
                            @change="onAvatarSelect"
                        />
                    </div>

                    <div class="flex flex-col gap-2">
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="avatarUploading"
                                @click="avatarFileRef?.click()"
                            >
                                <Camera class="mr-1.5 size-4" />
                                {{ avatarUploading ? 'Uploading...' : 'Upload photo' }}
                            </Button>

                            <Button
                                v-if="user.avatar"
                                variant="outline"
                                size="sm"
                                :disabled="avatarResetting"
                                @click="resetAvatar"
                            >
                                <Trash2 class="mr-1.5 size-4" />
                                {{ avatarResetting ? 'Removing...' : 'Remove' }}
                            </Button>
                        </div>
                        <p class="text-xs text-muted-foreground">JPG, PNG, GIF or WebP. Max 2 MB.</p>
                        <p v-if="avatarError" class="text-xs text-destructive">{{ avatarError }}</p>
                    </div>
                </div>
            </div>

            <div class="border-t my-2" />

            <!-- Profile information section -->
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Profile information"
                    description="Update your name and email address"
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                        />
                        <InputError class="mt-2" :message="errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            A new verification link has been sent to your email
                            address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                            >Save</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
