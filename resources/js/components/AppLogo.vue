<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Message-Me';
</script>

<template>
    <div
        class="flex size-9 shrink-0 overflow-hidden rounded-md"
    >
        <AppLogoIcon class="size-full object-contain" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{
            appName
        }}</span>
    </div>
</template>
