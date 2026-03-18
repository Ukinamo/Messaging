<script setup lang="ts">
import { cn } from '@/lib/utils';
import { ImagePlus, Paperclip, Send, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const emit = defineEmits<{
    send: [body: string, attachments?: File[]];
    typing: [];
}>();

const body = ref('');
const attachments = ref<File[]>([]);
const fileInputRef = ref<HTMLInputElement | null>(null);
const textareaRef = ref<HTMLTextAreaElement | null>(null);

const previews = computed(() =>
    attachments.value.map((file) => ({
        file,
        url: file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
        name: file.name,
        isImage: file.type.startsWith('image/'),
    })),
);

function handleSubmit() {
    if (!body.value.trim() && attachments.value.length === 0) return;
    emit('send', body.value, attachments.value.length > 0 ? attachments.value : undefined);
    body.value = '';
    attachments.value = [];
    adjustHeight();
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSubmit();
    } else {
        emit('typing');
    }
}

function handleInput() {
    adjustHeight();
    emit('typing');
}

function adjustHeight() {
    if (!textareaRef.value) return;
    textareaRef.value.style.height = 'auto';
    textareaRef.value.style.height = Math.min(textareaRef.value.scrollHeight, 120) + 'px';
}

function onFileSelect(e: Event) {
    const input = e.target as HTMLInputElement;
    if (input.files) {
        attachments.value.push(...Array.from(input.files));
    }
    input.value = '';
}

function removeAttachment(index: number) {
    attachments.value.splice(index, 1);
}

const canSend = computed(() => body.value.trim().length > 0 || attachments.value.length > 0);
</script>

<template>
    <div class="border-t px-4 pb-3 pt-2">
        <!-- Attachment preview strip -->
        <div v-if="previews.length > 0" class="mb-2 flex gap-2 overflow-x-auto pb-1">
            <div
                v-for="(preview, idx) in previews"
                :key="idx"
                class="bg-muted relative shrink-0 overflow-hidden rounded-lg"
            >
                <img
                    v-if="preview.isImage && preview.url"
                    :src="preview.url"
                    :alt="preview.name"
                    class="size-16 object-cover"
                />
                <div v-else class="flex size-16 items-center justify-center p-1">
                    <span class="text-muted-foreground truncate text-[10px]">{{ preview.name }}</span>
                </div>
                <button
                    class="bg-foreground/70 text-background absolute top-0.5 right-0.5 rounded-full p-0.5"
                    @click="removeAttachment(idx)"
                >
                    <X class="size-3" />
                </button>
            </div>
        </div>

        <div class="flex items-end gap-2">
            <input
                ref="fileInputRef"
                type="file"
                multiple
                class="hidden"
                accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar"
                @change="onFileSelect"
            />

            <button
                class="text-muted-foreground hover:text-foreground mb-1.5 transition-colors"
                title="Attach file"
                @click="fileInputRef?.click()"
            >
                <Paperclip class="size-5" />
            </button>

            <div class="bg-muted flex-1 rounded-2xl px-3 py-1.5">
                <textarea
                    ref="textareaRef"
                    v-model="body"
                    rows="1"
                    placeholder="Type a message..."
                    class="block w-full resize-none bg-transparent text-sm leading-relaxed outline-none placeholder:text-muted-foreground"
                    @keydown="handleKeydown"
                    @input="handleInput"
                />
            </div>

            <button
                :class="
                    cn(
                        'mb-1 flex size-8 items-center justify-center rounded-full transition-colors',
                        canSend
                            ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                            : 'text-muted-foreground bg-muted',
                    )
                "
                :disabled="!canSend"
                title="Send message"
                @click="handleSubmit"
            >
                <Send class="size-4" />
            </button>
        </div>
    </div>
</template>
