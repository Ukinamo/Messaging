<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <Head title="Log in" />

    <div class="login-container">
        <div class="hero-background">
            <div class="hero-gradient-1" />
            <div class="hero-gradient-2" />
        </div>

        <div class="login-wrapper">
            <div class="login-header">
                <img
                    src="/message_logo.png"
                    alt=""
                    class="auth-page-logo"
                />
                <h1>Welcome Back</h1>
                <p>Sign in to your messaging account</p>
            </div>

            <div
                v-if="status"
                class="mb-4 rounded-lg px-4 py-3 text-center text-sm font-medium text-[var(--success)]"
                style="
                    background: rgba(16, 185, 129, 0.1);
                    border: 1px solid rgba(16, 185, 129, 0.3);
                "
            >
                {{ status }}
            </div>

            <div class="card login-card">
                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                >
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
                                autofocus
                                autocomplete="email"
                                tabindex="1"
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
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                tabindex="2"
                                placeholder="••••••••"
                            />
                        </div>
                        <InputError :message="errors.password" />
                    </div>

                    <div class="form-footer">
                        <label class="checkbox-label">
                            <input
                                id="remember"
                                name="remember"
                                type="checkbox"
                                value="1"
                                tabindex="3"
                            />
                            <span>Remember me</span>
                        </label>
                        <Link
                            v-if="canResetPassword"
                            :href="request()"
                            class="forgot-link"
                            tabindex="5"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary btn-full"
                        :disabled="processing"
                        tabindex="4"
                        data-test="login-button"
                    >
                        <span v-if="!processing" class="btn-content">
                            Sign In
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
                            Signing in…
                        </span>
                    </button>
                </Form>
            </div>

            <div v-if="canRegister" class="auth-footer">
                <p>
                    Don't have an account?
                    <Link :href="register()" class="auth-link">Create one</Link>
                </p>
            </div>
        </div>
    </div>
</template>
