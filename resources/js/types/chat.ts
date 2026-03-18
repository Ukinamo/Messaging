export type MessageType = 'text' | 'image' | 'video' | 'file' | 'audio' | 'system';

export type ConversationType = 'private' | 'group';

export type ChatParticipant = {
    id: number;
    name: string;
    avatar?: string | null;
};

export type MessageFile = {
    path: string;
    url: string;
    name: string;
    mime: string;
    size: number;
};

export type MessageMetadata = {
    files?: MessageFile[];
};

export type ReactionUser = {
    id: number;
    name: string;
};

export type ReactionGroup = {
    emoji: string;
    count: number;
    users: ReactionUser[];
};

export type ChatMessage = {
    id: number;
    conversation_id: number;
    sender_id: number;
    sender: ChatParticipant;
    type: MessageType;
    body: string | null;
    metadata: MessageMetadata | null;
    parent_id: number | null;
    created_at: string;
    reactions: ReactionGroup[];
};

export type LatestMessage = {
    id: number;
    body: string | null;
    type: MessageType;
    sender_name: string | null;
    created_at: string;
};

export type ConversationSummary = {
    id: number;
    type: ConversationType;
    name: string;
    avatar: string | null;
    participants: ChatParticipant[];
    latest_message: LatestMessage | null;
    unread_count: number;
};

export type ActiveConversation = {
    id: number;
    type: ConversationType;
    name: string;
    avatar: string | null;
    participants: ChatParticipant[];
    messages: ChatMessage[];
    last_read_by_others?: number;
};

export type ChatPageProps = {
    conversations: ConversationSummary[];
    activeConversation?: ActiveConversation;
};
