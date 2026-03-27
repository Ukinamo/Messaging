import type { ComputedRef, Ref } from 'vue';
import { computed, onMounted, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
};

/** Reactive OS dark preference (used when appearance === 'system') */
const systemPrefersDark = ref(false);

function syncSystemPrefersDark(): void {
    if (typeof window === 'undefined') {
        return;
    }

    systemPrefersDark.value = window.matchMedia(
        '(prefers-color-scheme: dark)',
    ).matches;
}

if (typeof window !== 'undefined') {
    syncSystemPrefersDark();
}

export function updateTheme(value: Appearance): void {
    if (typeof window === 'undefined') {
        return;
    }

    if (value === 'system') {
        const mediaQueryList = window.matchMedia(
            '(prefers-color-scheme: dark)',
        );
        systemPrefersDark.value = mediaQueryList.matches;
        const systemTheme = mediaQueryList.matches ? 'dark' : 'light';

        document.documentElement.classList.toggle(
            'dark',
            systemTheme === 'dark',
        );
    } else {
        document.documentElement.classList.toggle('dark', value === 'dark');
    }
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

function normalizeAppearance(raw: string | null): Appearance | null {
    if (raw === 'light' || raw === 'dark' || raw === 'system') {
        return raw;
    }

    return null;
}

const getStoredAppearance = (): Appearance | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    return normalizeAppearance(localStorage.getItem('appearance'));
};

const handleSystemThemeChange = () => {
    syncSystemPrefersDark();
    const currentAppearance = getStoredAppearance();

    updateTheme(currentAppearance || 'system');
};

export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    // Initialize theme from saved preference or default to system...
    const savedAppearance = getStoredAppearance();
    updateTheme(savedAppearance || 'system');

    // Set up system theme change listener...
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

function readStoredAppearance(): Appearance {
    if (typeof window === 'undefined') {
        return 'system';
    }

    return normalizeAppearance(localStorage.getItem('appearance')) ?? 'system';
}

/** Keep in sync with initializeTheme() / localStorage so first paint matches <html class="dark"> */
const appearance = ref<Appearance>(readStoredAppearance());

let didSyncAppearanceFromStorage = false;

export function useAppearance(): UseAppearanceReturn {
    onMounted(() => {
        if (didSyncAppearanceFromStorage) {
            return;
        }

        didSyncAppearanceFromStorage = true;
        appearance.value = readStoredAppearance();
        updateTheme(appearance.value);
    });

    const resolvedAppearance = computed<ResolvedAppearance>(() => {
        if (appearance.value === 'system') {
            return systemPrefersDark.value ? 'dark' : 'light';
        }

        return appearance.value;
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;

        // Store in localStorage for client-side persistence...
        localStorage.setItem('appearance', value);

        // Store in cookie for SSR...
        setCookie('appearance', value);

        updateTheme(value);
    }

    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
    };
}
