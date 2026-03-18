<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Search, UserPlus } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import ChatAvatar from './ChatAvatar.vue';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [open: boolean];
    selectUser: [userId: number];
}>();

const query = ref('');
const results = ref<Array<{ id: number; name: string; email: string; avatar: string | null }>>([]);
const loading = ref(false);
let debounceTimer: ReturnType<typeof setTimeout> | null = null;

import axios from 'axios';

watch(query, (val) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    if (val.trim().length < 1) {
        results.value = [];
        return;
    }
    loading.value = true;
    debounceTimer = setTimeout(async () => {
        try {
            const { data } = await axios.get('/api/chat/users/search', { params: { q: val.trim() } });
            results.value = data.users;
        } catch {
            results.value = [];
        } finally {
            loading.value = false;
        }
    }, 300);
});

function handleSelect(userId: number) {
    emit('selectUser', userId);
    emit('update:open', false);
    query.value = '';
    results.value = [];
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogTitle>New Conversation</DialogTitle>
            <DialogDescription>Search for a user to start chatting with.</DialogDescription>

            <div class="relative mt-2">
                <Search class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                <Input
                    v-model="query"
                    placeholder="Search by name or email..."
                    class="pl-9"
                    autofocus
                />
            </div>

            <div class="mt-2 max-h-64 overflow-y-auto">
                <div v-if="loading" class="flex justify-center py-6">
                    <Spinner class="size-5" />
                </div>

                <div v-else-if="results.length > 0" class="space-y-1">
                    <button
                        v-for="user in results"
                        :key="user.id"
                        class="hover:bg-muted flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left transition-colors"
                        @click="handleSelect(user.id)"
                    >
                        <ChatAvatar :name="user.name" :avatar="user.avatar" size="sm" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ user.name }}</p>
                            <p class="text-muted-foreground truncate text-xs">{{ user.email }}</p>
                        </div>
                        <UserPlus class="text-muted-foreground size-4 shrink-0" />
                    </button>
                </div>

                <p
                    v-else-if="query.trim().length > 0 && !loading"
                    class="text-muted-foreground py-6 text-center text-sm"
                >
                    No users found
                </p>

                <p v-else class="text-muted-foreground py-6 text-center text-sm">
                    Start typing to search for users
                </p>
            </div>
        </DialogContent>
    </Dialog>
</template>
