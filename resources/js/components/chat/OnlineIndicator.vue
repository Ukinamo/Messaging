<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = withDefaults(
    defineProps<{
        online: boolean;
        class?: HTMLAttributes['class'];
    }>(),
    { online: false },
);
</script>

<template>
    <span
        v-if="online"
        :class="
            cn(
                'absolute bottom-0 right-0 block size-3 rounded-full bg-emerald-500 ring-2 ring-background',
                props.class,
            )
        "
    >
        <span class="absolute inset-0 animate-ping rounded-full bg-emerald-400 opacity-60" />
    </span>
    <span
        v-else
        :class="
            cn(
                'absolute bottom-0 right-0 block size-2.5 rounded-full bg-zinc-300 ring-2 ring-background dark:bg-zinc-600',
                props.class,
            )
        "
    />
</template>
