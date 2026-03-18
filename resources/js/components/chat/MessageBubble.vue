<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { ChatMessage, ReactionGroup } from '@/types';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Check,
    CheckCheck,
    Download,
    FileText,
    Forward,
    MoreVertical,
    SmilePlus,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    message: ChatMessage;
    isMine: boolean;
    showSender?: boolean;
    isRead?: boolean;
    authUserId: number;
}>();

const emit = defineEmits<{
    deleteForMe: [messageId: number];
    deleteForEveryone: [messageId: number];
    react: [messageId: number, emoji: string];
    forward: [messageId: number];
}>();

const showActions = ref(false);
const showEmojiPicker = ref(false);

const quickEmojis = ['👍', '❤️', '😂', '😮', '😢', '🙏'];

function formatTime(dateStr: string): string {
    return new Date(dateStr).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    });
}

const files = computed(() => props.message.metadata?.files ?? []);

function formatFileSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
}

function isImage(mime: string): boolean {
    return mime.startsWith('image/');
}

function hasUserReacted(reaction: ReactionGroup): boolean {
    return reaction.users.some((u) => u.id === props.authUserId);
}

function reactionTooltip(reaction: ReactionGroup): string {
    return reaction.users.map((u) => u.name).join(', ');
}
</script>

<template>
    <div
        :class="cn('group flex gap-2', isMine ? 'flex-row-reverse' : 'flex-row')"
        @mouseenter="showActions = true"
        @mouseleave="showActions = false; showEmojiPicker = false"
    >
        <div :class="cn('max-w-[75%] space-y-1', isMine ? 'items-end' : 'items-start')">
            <p
                v-if="showSender && !isMine"
                class="text-muted-foreground mb-0.5 px-1 text-xs font-medium"
            >
                {{ message.sender?.name ?? 'Unknown' }}
            </p>

            <!-- Image attachments -->
            <div v-if="files.length > 0 && files.some((f) => isImage(f.mime))" class="space-y-1">
                <div
                    v-for="file in files.filter((f) => isImage(f.mime))"
                    :key="file.path"
                    class="overflow-hidden rounded-xl"
                >
                    <a :href="file.url" target="_blank" class="block">
                        <img
                            :src="file.url"
                            :alt="file.name"
                            class="max-h-64 max-w-full rounded-xl object-cover"
                            loading="lazy"
                        />
                    </a>
                </div>
            </div>

            <!-- File attachments (non-image) -->
            <div v-if="files.length > 0 && files.some((f) => !isImage(f.mime))">
                <a
                    v-for="file in files.filter((f) => !isImage(f.mime))"
                    :key="file.path"
                    :href="file.url"
                    target="_blank"
                    :class="
                        cn(
                            'flex items-center gap-2 rounded-xl px-3 py-2 text-sm',
                            isMine
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-foreground',
                        )
                    "
                >
                    <FileText class="size-4 shrink-0" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ file.name }}</p>
                        <p class="text-xs opacity-75">{{ formatFileSize(file.size) }}</p>
                    </div>
                    <Download class="size-4 shrink-0 opacity-75" />
                </a>
            </div>

            <!-- Text body -->
            <div
                v-if="message.body"
                :class="
                    cn(
                        'inline-block rounded-2xl px-3.5 py-2 text-sm leading-relaxed',
                        isMine
                            ? 'bg-primary text-primary-foreground rounded-br-md'
                            : 'bg-muted text-foreground rounded-bl-md',
                    )
                "
            >
                <p class="whitespace-pre-wrap break-words">{{ message.body }}</p>
            </div>

            <!-- Reactions display -->
            <div
                v-if="message.reactions && message.reactions.length > 0"
                class="flex flex-wrap gap-1 px-1"
            >
                <button
                    v-for="reaction in message.reactions"
                    :key="reaction.emoji"
                    :title="reactionTooltip(reaction)"
                    :class="
                        cn(
                            'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs transition-colors',
                            hasUserReacted(reaction)
                                ? 'border-primary/40 bg-primary/10 text-primary'
                                : 'border-border bg-background text-muted-foreground hover:bg-muted',
                        )
                    "
                    @click="emit('react', message.id, reaction.emoji)"
                >
                    <span>{{ reaction.emoji }}</span>
                    <span class="font-medium">{{ reaction.count }}</span>
                </button>
            </div>

            <div
                :class="
                    cn(
                        'flex items-center gap-1 px-1',
                        isMine ? 'justify-end' : 'justify-start',
                    )
                "
            >
                <span class="text-muted-foreground text-[10px]">
                    {{ formatTime(message.created_at) }}
                </span>
                <CheckCheck v-if="isMine && isRead" class="size-3 text-blue-500" />
                <Check v-else-if="isMine" class="text-muted-foreground size-3" />
            </div>
        </div>

        <!-- Action bar (visible on hover) -->
        <div
            v-show="showActions"
            :class="cn('flex items-center gap-0.5', isMine ? 'flex-row-reverse' : 'flex-row')"
        >
            <!-- Quick emoji row -->
            <div class="relative">
                <button
                    class="text-muted-foreground hover:text-foreground rounded p-1 transition-colors"
                    title="React"
                    @click="showEmojiPicker = !showEmojiPicker"
                >
                    <SmilePlus class="size-3.5" />
                </button>
                <div
                    v-if="showEmojiPicker"
                    :class="
                        cn(
                            'bg-popover border-border absolute z-50 flex gap-0.5 rounded-lg border p-1.5 shadow-lg',
                            isMine ? 'right-0' : 'left-0',
                            'bottom-full mb-1',
                        )
                    "
                >
                    <button
                        v-for="emoji in quickEmojis"
                        :key="emoji"
                        class="rounded px-1 py-0.5 text-base transition-transform hover:scale-125 hover:bg-muted"
                        @click="emit('react', message.id, emoji); showEmojiPicker = false"
                    >
                        {{ emoji }}
                    </button>
                </div>
            </div>

            <!-- More actions dropdown -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button
                        class="text-muted-foreground hover:text-foreground rounded p-1 transition-colors"
                        title="More"
                    >
                        <MoreVertical class="size-3.5" />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent :align="isMine ? 'end' : 'start'" class="w-48">
                    <DropdownMenuItem @click="emit('forward', message.id)">
                        <Forward class="mr-2 size-4" />
                        Forward
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        class="text-destructive focus:text-destructive"
                        @click="emit('deleteForMe', message.id)"
                    >
                        <Trash2 class="mr-2 size-4" />
                        Delete for me
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        v-if="isMine"
                        class="text-destructive focus:text-destructive"
                        @click="emit('deleteForEveryone', message.id)"
                    >
                        <Trash2 class="mr-2 size-4" />
                        Delete for everyone
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </div>
</template>
