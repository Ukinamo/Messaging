import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import { initializeTheme } from '@/composables/useAppearance';
import { startPresenceChannel } from '@/composables/usePresence';

const appName = import.meta.env.VITE_APP_NAME || 'Message-Me';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);

        const user = (props as { initialPage?: { props?: { auth?: { user?: unknown } } } }).initialPage?.props?.auth?.user;

        if (user) {
            startPresenceChannel();
        }

        router.on('success', (event) => {
            const pageUser = (event.detail.page.props as { auth?: { user?: unknown } }).auth?.user;

            if (pageUser) {
                startPresenceChannel();
            }
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
