import { ref } from 'vue';
import echo from '@/echo';

const onlineUserIds = ref<Set<number>>(new Set());
let joined = false;

export function startPresenceChannel() {
    if (joined) {
        return;
    }

    joined = true;
    echo.join('presence-chat')
        .here((users: Array<{ id: number }>) => {
            onlineUserIds.value = new Set(users.map((u) => u.id));
        })
        .joining((user: { id: number }) => {
            onlineUserIds.value.add(user.id);
            onlineUserIds.value = new Set(onlineUserIds.value);
        })
        .leaving((user: { id: number }) => {
            onlineUserIds.value.delete(user.id);
            onlineUserIds.value = new Set(onlineUserIds.value);
        });
}

export function usePresence() {
    return {
        onlineUserIds,
        startPresenceChannel,
    };
}

