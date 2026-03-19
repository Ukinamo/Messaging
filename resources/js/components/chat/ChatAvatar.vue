<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import OnlineIndicator from './OnlineIndicator.vue';

defineProps<{
    name: string;
    avatar?: string | null;
    online?: boolean;
    showStatus?: boolean;
    size?: 'sm' | 'md' | 'lg';
}>();

function initials(name: string): string {
    if (!name) return '?';
    return name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

const sizeClasses = {
    sm: 'size-8 text-xs',
    md: 'size-10 text-sm',
    lg: 'size-14 text-lg',
};
</script>

<template>
    <div class="relative inline-block">
        <Avatar :class="sizeClasses[size || 'md']">
            <AvatarImage v-if="avatar" :src="avatar" :alt="name" />
            <AvatarFallback class="bg-primary/10 text-primary font-medium">
                {{ initials(name) }}
            </AvatarFallback>
        </Avatar>
        <OnlineIndicator v-if="showStatus" :online="!!online" />
    </div>
</template>
