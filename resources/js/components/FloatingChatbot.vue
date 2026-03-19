<script setup lang="ts">
import axios from 'axios';
import { Bot, Send, X } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

type ChatMessage = {
    id: string;
    role: 'user' | 'assistant';
    content: string;
    createdAt: number;
};

function uuid() {
    const cryptoObj = globalThis.crypto as Crypto | undefined;
    const maybe = cryptoObj?.randomUUID?.bind(cryptoObj);

    if (maybe) {
        return maybe();
    }

    return `${Date.now().toString(16)}-${Math.random().toString(16).slice(2)}-${Math.random().toString(16).slice(2)}`;
}

const isOpen = ref(false);
const input = ref('');
const isSending = ref(false);
const errorText = ref<string | null>(null);
const posX = ref(0);
const posY = ref(0);
const isDragging = ref(false);
const dragStart = ref<{ x: number; y: number; posX: number; posY: number } | null>(null);
const containerEl = ref<HTMLElement | null>(null);
const messages = ref<ChatMessage[]>([
    {
        id: uuid(),
        role: 'assistant',
        content: "Hi! I'm your assistant. How can I help?",
        createdAt: Date.now(),
    },
]);

const listEl = ref<HTMLElement | null>(null);

const canSend = computed(() => input.value.trim().length > 0);

const DRAG_THRESHOLD = 6;

function clampPosition(x: number, y: number) {
    const w = typeof window !== 'undefined' ? window.innerWidth : 400;
    const h = typeof window !== 'undefined' ? window.innerHeight : 600;
    const el = containerEl.value;
    const width = el ? el.offsetWidth : 360;
    const height = el ? el.offsetHeight : 80;

    return {
        x: Math.max(0, Math.min(x, w - width)),
        y: Math.max(0, Math.min(y, h - height)),
    };
}

function startDrag(e: MouseEvent | TouchEvent) {
    const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
    const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;
    isDragging.value = true;
    dragStart.value = { x: clientX, y: clientY, posX: posX.value, posY: posY.value };

    if (e.cancelable) {
        e.preventDefault();
    }
}

function onDragMove(e: MouseEvent | TouchEvent) {
    if (!dragStart.value) {
        return;
    }

    const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
    const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;
    const dx = clientX - dragStart.value.x;
    const dy = clientY - dragStart.value.y;
    const { x, y } = clampPosition(dragStart.value.posX + dx, dragStart.value.posY + dy);
    posX.value = x;
    posY.value = y;

    if (e.cancelable) {
        e.preventDefault();
    }
}

function stopDrag(e: MouseEvent | TouchEvent, fromButton: boolean) {
    const clientX = 'changedTouches' in e ? e.changedTouches[0].clientX : (e as MouseEvent).clientX;
    const clientY = 'changedTouches' in e ? e.changedTouches[0].clientY : (e as MouseEvent).clientY;
    const start = dragStart.value;
    dragStart.value = null;
    isDragging.value = false;

    if (fromButton && start) {
        const dx = clientX - start.x;
        const dy = clientY - start.y;

        if (Math.hypot(dx, dy) < DRAG_THRESHOLD) {
            isOpen.value = !isOpen.value;
        }
    }
}

function setupDragListeners(fromButton: boolean) {
    const move = (e: MouseEvent | TouchEvent) => onDragMove(e);
    const up = (e: MouseEvent | TouchEvent) => {
        stopDrag(e, fromButton);
        window.removeEventListener('mousemove', move as (e: MouseEvent) => void);
        window.removeEventListener('mouseup', up as (e: MouseEvent) => void);
        window.removeEventListener('touchmove', move, { passive: false });
        window.removeEventListener('touchend', up);
    };
    window.addEventListener('mousemove', move as (e: MouseEvent) => void);
    window.addEventListener('mouseup', up as (e: MouseEvent) => void);
    window.addEventListener('touchmove', move, { passive: false });
    window.addEventListener('touchend', up);
}

function onHandlePointerDown(e: MouseEvent | TouchEvent, fromButton: boolean) {
    startDrag(e);
    setupDragListeners(fromButton);
}

onMounted(() => {
    posX.value = typeof window !== 'undefined' ? window.innerWidth - 60 : 340;
    posY.value = typeof window !== 'undefined' ? window.innerHeight - 80 : 520;
});

function scrollToBottom() {
    const el = listEl.value;

    if (!el) {
        return;
    }

    el.scrollTop = el.scrollHeight;
}

async function send() {
    const text = input.value.trim();

    if (!text) {
        return;
    }

    errorText.value = null;
    messages.value.push({
        id: uuid(),
        role: 'user',
        content: text,
        createdAt: Date.now(),
    });
    input.value = '';

    await nextTick();
    scrollToBottom();

    isSending.value = true;
    errorText.value = null;

    const history = messages.value
        .filter((m) => m.role === 'user' || m.role === 'assistant')
        .slice(-12)
        .map((m) => ({ role: m.role, content: m.content }));

    const doRequest = async (retryCount = 0): Promise<void> => {
        try {
            const { data } = await axios.post('/api/chat/assistant', { messages: history });
            const reply = data?.message?.content;

            if (typeof reply !== 'string' || reply.trim() === '') {
                throw new Error('Empty AI response');
            }

            messages.value.push({
                id: uuid(),
                role: 'assistant',
                content: reply,
                createdAt: Date.now(),
            });

            await nextTick();
            scrollToBottom();
        } catch (err: unknown) {
            const res = err && typeof err === 'object' && 'response' in err ? (err as { response?: { data?: { message?: string }; status?: number } }).response : undefined;
            const msg = res?.data?.message ?? 'AI is unavailable. Check GEMINI_API_KEY in .env and try again.';

            const isRateLimit = res?.status === 429 || /quota|rate limit|retry in/i.test(msg);
            const canRetry = isRateLimit && retryCount === 0;

            if (canRetry) {
                const retryMatch = msg.match(/retry in (\d+(?:\.\d+)?)\s*s/i);
                const secs = retryMatch ? Math.ceil(Number(retryMatch[1])) : 45;
                const waitSecs = Math.min(secs, 60);

                errorText.value = `Rate limited. Retrying in ${waitSecs} seconds…`;
                let left = waitSecs;
                const tick = setInterval(() => {
                    left -= 1;

                    if (left <= 0) {
                        clearInterval(tick);
                        errorText.value = null;
                        void doRequest(1);
                    } else {
                        errorText.value = `Rate limited. Retrying in ${left} seconds…`;
                    }
                }, 1000);

                return;
            }

            if (isRateLimit) {
                errorText.value = 'Rate limit reached. Please wait a minute and try again.';
            } else {
                errorText.value = msg;
            }

        } finally {
            isSending.value = false;
        }
    };

    await doRequest();
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && isOpen.value) {
        isOpen.value = false;
    }

    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        void send();
    }
}

function onDocKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && isOpen.value) {
        isOpen.value = false;
    }
}

watch(isOpen, async (open: boolean, prev: boolean) => {
    if (open) {
        document.addEventListener('keydown', onDocKeydown);

        await nextTick();
        scrollToBottom();

        return;
    }

    if (prev) {
        document.removeEventListener('keydown', onDocKeydown);
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onDocKeydown);
});
</script>

<template>
    <Teleport to="body">
        <div
            ref="containerEl"
            class="fixed z-60 h-12 w-12"
            :style="{ left: posX + 'px', top: posY + 'px' }"
        >
            <!-- Chat panel: always above and to the left of the button (panel's bottom-right = button's top-left) -->
            <div
                v-if="isOpen"
                class="absolute right-[calc(100%+0.5rem)] bottom-[calc(100%+0.5rem)] w-88 overflow-hidden rounded-xl border bg-background shadow-xl"
            >
                <div
                    class="flex cursor-grab items-center justify-between border-b px-4 py-3 select-none active:cursor-grabbing"
                    :class="{ 'cursor-grabbing': isDragging }"
                    @mousedown.prevent="onHandlePointerDown($event, false)"
                    @touchstart.prevent="onHandlePointerDown($event, false)"
                >
                    <div class="flex items-center gap-2">
                        <Bot class="h-5 w-5 text-primary" />
                        <div class="text-sm font-semibold">Chat bot</div>
                    </div>
                    <button
                        type="button"
                        class="rounded-md p-2 text-muted-foreground hover:bg-muted hover:text-foreground"
                        aria-label="Close chat"
                        @mousedown.stop
                        @click="isOpen = false"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div ref="listEl" class="max-h-80 space-y-3 overflow-y-auto px-4 py-3">
                    <div
                        v-for="m in messages"
                        :key="m.id"
                        class="flex"
                        :class="m.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[85%] rounded-2xl px-3 py-2 text-sm leading-relaxed"
                            :class="
                                m.role === 'user'
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-foreground'
                            "
                        >
                            {{ m.content }}
                        </div>
                    </div>

                    <div v-if="errorText" class="rounded-lg border border-destructive/30 bg-destructive/10 p-2 text-sm">
                        {{ errorText }}
                    </div>
                </div>

                <form class="flex items-end gap-2 border-t p-3" @submit.prevent="send">
                    <textarea
                        v-model="input"
                        class="min-h-10 max-h-24 flex-1 resize-none rounded-md border bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        placeholder="Type a message…"
                        @keydown="onKeydown"
                    />
                    <button
                        type="submit"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-primary text-primary-foreground disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!canSend || isSending"
                        aria-label="Send message"
                    >
                        <Send class="h-4 w-4" />
                    </button>
                </form>
            </div>

            <button
                type="button"
                class="absolute bottom-0 right-0 flex h-12 w-12 cursor-grab items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg hover:opacity-95 active:cursor-grabbing"
                :class="{ 'cursor-grabbing': isDragging }"
                aria-label="Open chat"
                @mousedown.prevent="onHandlePointerDown($event, true)"
                @touchstart.prevent="onHandlePointerDown($event, true)"
            >
                <Bot class="h-5 w-5" />
            </button>
        </div>
    </Teleport>
</template>

