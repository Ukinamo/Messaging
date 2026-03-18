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
        :class="
            cn(
                'absolute bottom-0 right-0 block size-2.5 rounded-full ring-2 ring-background transition-colors duration-300',
                online ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-600',
                props.class,
            )
        "
    >
        <span
            v-if="online"
            class="absolute inset-0 animate-ping rounded-full bg-emerald-400 opacity-75"
        />
    </span>
</template>
