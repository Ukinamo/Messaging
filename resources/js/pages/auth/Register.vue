<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { computed, reactive } from 'vue';

/** Only for client-side “passwords match” hint; other fields use native form names. */
const pwd = reactive({
    password: '',
    password_confirmation: '',
});

const passwordMismatch = computed(() => {
    return (
        pwd.password !== pwd.password_confirmation && pwd.password !== ''
    );
});
</script>

<template>
    <Head title="Register" />

    <div class="register-container">
        <div class="hero-background">
            <div class="hero-gradient-1" />
            <div class="hero-gradient-2" />
        </div>

        <div class="register-wrapper">
            <div class="register-header">
                <img
                    src="/message_logo.png"
                    alt=""
                    class="auth-page-logo"
                />
                <h1>Join Us</h1>
                <p>Create your messaging account today</p>
            </div>

            <div class="card register-card">
                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing }"
                >
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <div class="input-wrapper">
                            <svg
                                class="input-icon"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                            <input
                                id="name"
                                type="text"
                                name="name"
                                required
                                autofocus
                                autocomplete="name"
                                tabindex="1"
                                placeholder="John Doe"
                            />
                        </div>
                        <InputError :message="errors.name" />
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <svg
                                class="input-icon"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                />
                            </svg>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autocomplete="email"
                                tabindex="2"
                                placeholder="you@example.com"
                            />
                        </div>
                        <InputError :message="errors.email" />
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <svg
                                class="input-icon"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                />
                            </svg>
                            <input
                                id="password"
                                v-model="pwd.password"
                                type="password"
                                name="password"
                                required
                                autocomplete="new-password"
                                tabindex="3"
                                placeholder="••••••••"
                            />
                        </div>
                        <InputError :message="errors.password" />
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation"
                            >Confirm Password</label
                        >
                        <div class="input-wrapper">
                            <svg
                                class="input-icon"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                />
                            </svg>
                            <input
                                id="password_confirmation"
                                v-model="pwd.password_confirmation"
                                type="password"
                                name="password_confirmation"
                                required
                                autocomplete="new-password"
                                tabindex="4"
                                placeholder="••••••••"
                                :class="{
                                    'input-error':
                                        passwordMismatch &&
                                        pwd.password_confirmation !== '',
                                }"
                            />
                        </div>
                        <div
                            v-if="
                                passwordMismatch &&
                                pwd.password_confirmation !== ''
                            "
                            class="password-error"
                        >
                            Passwords do not match
                        </div>
                        <div
                            v-else-if="
                                !passwordMismatch &&
                                pwd.password_confirmation !== ''
                            "
                            class="password-success"
                        >
                            ✓ Passwords match
                        </div>
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary btn-full"
                        :disabled="processing || passwordMismatch"
                        tabindex="5"
                        data-test="register-user-button"
                    >
                        <span v-if="!processing" class="btn-content">
                            Create Account
                            <svg
                                width="16"
                                height="16"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6"
                                />
                            </svg>
                        </span>
                        <span v-else class="btn-content">
                            <span class="auth-spinner" />
                            Creating account…
                        </span>
                    </button>
                </Form>
            </div>

            <div class="auth-footer">
                <p>
                    Already have an account?
                    <Link :href="login()" class="auth-link" tabindex="6"
                        >Sign in</Link
                    >
                </p>
            </div>
        </div>
    </div>
</template>
