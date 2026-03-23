<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    MessageCircle,
    Shield,
    Users,
    Zap,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { dashboard, home, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const appName = import.meta.env.VITE_APP_NAME || 'MessageHub';
const year = new Date().getFullYear();
const featuresSection = ref<HTMLElement | null>(null);

const features = [
    {
        id: 1,
        title: 'Real-time Chat',
        description:
            'Instant messaging with real-time notifications and typing indicators',
        icon: MessageCircle,
    },
    {
        id: 2,
        title: 'Group Chats',
        description:
            'Create and manage group conversations with unlimited participants',
        icon: Users,
    },
    {
        id: 3,
        title: 'End-to-End Encryption',
        description:
            'Military-grade encryption keeps your conversations completely private',
        icon: Shield,
    },
    {
        id: 4,
        title: 'Lightning Fast',
        description:
            'Optimized for speed with minimal latency and maximum reliability',
        icon: Zap,
    },
];

function scrollToFeatures() {
    featuresSection.value?.scrollIntoView({ behavior: 'smooth' });
}
</script>

<template>
    <Head :title="`Welcome — ${appName}`" />

    <div class="home-container min-h-screen bg-[var(--background-pearl)]">
        <nav class="navbar">
            <div class="container">
                <div class="navbar-content">
                    <Link :href="home()" class="navbar-brand">
                        <img
                            src="/message_logo.png"
                            alt=""
                            class="h-10 w-10 rounded-full object-cover"
                        />
                        <span>{{ appName }}</span>
                    </Link>
                    <div class="navbar-nav">
                        <template v-if="$page.props.auth.user">
                            <Link :href="dashboard()" class="btn btn-ghost">
                                Dashboard
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="login()" class="btn btn-ghost">
                                Sign In
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="register()"
                                class="btn btn-primary"
                            >
                                Get Started
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <section class="hero">
            <div class="hero-background">
                <div class="hero-gradient-1" />
                <div class="hero-gradient-2" />
            </div>

            <div class="container">
                <div class="hero-content">
                    <div class="hero-text">
                        <h1>
                            Connect with
                            <span class="text-gradient">Confidence</span>
                        </h1>
                        <p>
                            A modern messaging platform designed for seamless
                            communication, security, and collaboration. Connect
                            with anyone, anywhere.
                        </p>

                        <div class="hero-buttons">
                            <Link
                                v-if="canRegister"
                                :href="register()"
                                class="btn btn-primary"
                            >
                                Start Messaging
                                <svg
                                    width="20"
                                    height="20"
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
                            </Link>
                            <button
                                type="button"
                                class="btn btn-secondary"
                                @click="scrollToFeatures"
                            >
                                Watch Demo
                            </button>
                        </div>

                        <div class="trust-indicators">
                            <div class="avatar-group">
                                <div
                                    v-for="i in 3"
                                    :key="i"
                                    class="avatar"
                                >
                                    {{ i }}
                                </div>
                            </div>
                            <p>
                                <span class="trust-number">10K+</span> users
                                already messaging
                            </p>
                        </div>
                    </div>

                    <div class="hero-image">
                        <img
                            src="https://d2xsxph8kpxj0f.cloudfront.net/310519663451317610/LMV4wPsSkQPjM6nGnQ2Q95/chat_illustration-CQgN4BuLiNASYuxLF6LnTq.webp"
                            alt="Messaging illustration"
                        />
                    </div>
                </div>
            </div>
        </section>

        <section id="features" ref="featuresSection" class="features">
            <div class="container">
                <div class="features-header">
                    <h2>Powerful Features</h2>
                    <p>
                        Everything you need for modern, secure, and efficient
                        messaging
                    </p>
                </div>

                <div class="features-grid">
                    <div
                        v-for="feature in features"
                        :key="feature.id"
                        class="card feature-card"
                    >
                        <div class="feature-icon">
                            <component
                                :is="feature.icon"
                                class="h-6 w-6"
                                stroke-width="2"
                            />
                        </div>
                        <h3>{{ feature.title }}</h3>
                        <p>{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="canRegister" class="cta-section">
            <div class="container">
                <div class="cta">
                    <h2>Ready to Start?</h2>
                    <p>
                        Join thousands of users who are already enjoying
                        seamless messaging. Sign up today and connect with your
                        community.
                    </p>
                    <Link :href="register()" class="btn btn-white">
                        Create Your Account
                        <svg
                            width="20"
                            height="20"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                            />
                        </svg>
                    </Link>
                </div>
            </div>
        </section>

        <footer>
            <div class="container">
                <div class="footer-content">
                    <div class="footer-section">
                        <div class="footer-brand">
                            <img
                                src="/message_logo.png"
                                alt=""
                                class="footer-logo"
                            />
                            <span>{{ appName }}</span>
                        </div>
                        <p>Modern messaging for everyone</p>
                    </div>

                    <div class="footer-section">
                        <h4>Product</h4>
                        <ul>
                            <li><a href="#features">Features</a></li>
                            <li><a href="#">Pricing</a></li>
                            <li><a href="#">Security</a></li>
                        </ul>
                    </div>

                    <div class="footer-section">
                        <h4>Company</h4>
                        <ul>
                            <li><a href="#">About</a></li>
                            <li><a href="#">Blog</a></li>
                            <li><a href="#">Careers</a></li>
                        </ul>
                    </div>

                    <div class="footer-section">
                        <h4>Legal</h4>
                        <ul>
                            <li><a href="#">Privacy</a></li>
                            <li><a href="#">Terms</a></li>
                            <li><a href="#">Contact</a></li>
                        </ul>
                    </div>
                </div>

                <div class="footer-bottom">
                    <p>&copy; {{ year }} {{ appName }}. All rights reserved.</p>
                    <div class="footer-social">
                        <a href="#">Twitter</a>
                        <a href="#">Facebook</a>
                        <a href="#">LinkedIn</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
