<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { ChatMessage, ReactionGroup } from '@/types';
import {
    Ban,
    Check,
    CheckCheck,
    Download,
    FileText,
    Forward,
    SmilePlus,
    Trash2,
    X,
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
const showDeleteOptions = ref(false);
const lightboxUrl = ref<string | null>(null);
const lightboxName = ref('');

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

const myReactionEmoji = computed(() => {
    if (!props.message.reactions) return null;
    for (const r of props.message.reactions) {
        if (r.users.some((u) => u.id === props.authUserId)) return r.emoji;
    }
    return null;
});

function openLightbox(url: string, name: string) {
    lightboxUrl.value = url;
    lightboxName.value = name;
}

function closeLightbox() {
    lightboxUrl.value = null;
}

const urlRegex = /(https?:\/\/[^\s<]+)/g;

function linkify(text: string): string {
    const escaped = text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
    return escaped.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener noreferrer" class="underline break-all hover:opacity-80">$1</a>');
}
</script>

<template>
    <!-- Deleted message placeholder -->
    <div
        v-if="message.deleted_for_everyone"
        :class="cn('group flex gap-2', isMine ? 'flex-row-reverse' : 'flex-row')"
    >
        <div :class="cn('max-w-[75%] space-y-1', isMine ? 'items-end' : 'items-start')">
            <p
                v-if="showSender && !isMine"
                class="text-muted-foreground mb-0.5 px-1 text-xs font-medium"
            >
                {{ message.sender?.name ?? 'Deleted User' }}
            </p>
            <div
                :class="cn(
                    'inline-flex items-center gap-1.5 rounded-2xl border border-dashed px-3.5 py-2 text-sm italic',
                    isMine
                        ? 'border-primary/30 text-primary/60 rounded-br-md'
                        : 'border-muted-foreground/30 text-muted-foreground rounded-bl-md',
                )"
            >
                <Ban class="size-3.5 shrink-0" />
                <span>This message was deleted</span>
            </div>
            <div :class="cn('flex items-center gap-1 px-1', isMine ? 'justify-end' : 'justify-start')">
                <span class="text-muted-foreground text-[10px]">{{ formatTime(message.created_at) }}</span>
            </div>
        </div>
    </div>

    <!-- Normal message -->
    <div
        v-else
        :class="cn('group flex gap-2', isMine ? 'flex-row-reverse' : 'flex-row')"
        @mouseenter="showActions = true"
        @mouseleave="showActions = false; showEmojiPicker = false; showDeleteOptions = false"
    >
        <div :class="cn('max-w-[75%] space-y-1', isMine ? 'items-end' : 'items-start')">
            <p
                v-if="showSender && !isMine"
                class="text-muted-foreground mb-0.5 px-1 text-xs font-medium"
            >
                {{ message.sender?.name ?? 'Deleted User' }}
            </p>

            <!-- Image attachments -->
            <div v-if="files.length > 0 && files.some((f) => isImage(f.mime))" class="space-y-1">
                <div
                    v-for="file in files.filter((f) => isImage(f.mime))"
                    :key="file.path"
                    class="overflow-hidden rounded-xl"
                >
                    <button class="block cursor-zoom-in" @click="openLightbox(file.url, file.name)">
                        <img
                            :src="file.url"
                            :alt="file.name"
                            class="max-h-64 max-w-full rounded-xl object-cover"
                            loading="lazy"
                        />
                    </button>
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
                <p class="whitespace-pre-wrap wrap-break-word" v-html="linkify(message.body)" />
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
            <!-- React -->
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
                        :class="cn(
                            'rounded px-1 py-0.5 text-base transition-transform hover:scale-125',
                            myReactionEmoji === emoji ? 'bg-primary/15 ring-primary/40 ring-1' : 'hover:bg-muted',
                        )"
                        @click="emit('react', message.id, emoji); showEmojiPicker = false"
                    >
                        {{ emoji }}
                    </button>
                </div>
            </div>

            <!-- Forward -->
            <button
                class="text-muted-foreground hover:text-foreground rounded p-1 transition-colors"
                title="Forward"
                @click="emit('forward', message.id)"
            >
                <Forward class="size-3.5" />
            </button>

            <!-- Delete with inline options -->
            <div class="relative flex items-center">
                <button
                    class="text-muted-foreground hover:text-destructive rounded p-1 transition-colors"
                    title="Delete"
                    @click="showDeleteOptions = !showDeleteOptions"
                >
                    <Trash2 class="size-3.5" />
                </button>
                <div
                    v-if="showDeleteOptions"
                    :class="cn(
                        'bg-popover border-border absolute z-50 flex items-center gap-0.5 rounded-lg border px-1 py-0.5 shadow-lg whitespace-nowrap',
                        isMine ? 'right-full mr-1' : 'left-full ml-1',
                    )"
                >
                    <button
                        class="text-destructive hover:bg-destructive/10 rounded px-2 py-1 text-[11px] font-medium transition-colors"
                        @click="emit('deleteForMe', message.id); showDeleteOptions = false"
                    >
                        For me
                    </button>
                    <button
                        v-if="isMine"
                        class="text-destructive hover:bg-destructive/10 rounded px-2 py-1 text-[11px] font-medium transition-colors"
                        @click="emit('deleteForEveryone', message.id); showDeleteOptions = false"
                    >
                        For everyone
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image lightbox overlay -->
    <Teleport to="body">
        <div
            v-if="lightboxUrl"
            class="fixed inset-0 z-100 flex items-center justify-center bg-black/80 backdrop-blur-sm"
            @click.self="closeLightbox"
        >
            <div class="absolute right-4 top-4 flex items-center gap-2">
                <a
                    :href="lightboxUrl"
                    :download="lightboxName"
                    class="rounded-full bg-white/10 p-2 text-white transition-colors hover:bg-white/20"
                    title="Download"
                >
                    <Download class="size-5" />
                </a>
                <button
                    class="rounded-full bg-white/10 p-2 text-white transition-colors hover:bg-white/20"
                    title="Close"
                    @click="closeLightbox"
                >
                    <X class="size-5" />
                </button>
            </div>
            <img
                :src="lightboxUrl"
                :alt="lightboxName"
                class="max-h-[90vh] max-w-[90vw] rounded-lg object-contain shadow-2xl"
            />
        </div>
    </Teleport>
</template>
