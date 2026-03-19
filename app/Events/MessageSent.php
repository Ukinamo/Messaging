<?php

namespace App\Events;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
    ) {
        $this->message->load(['sender', 'reactions']);
    }

    public function broadcastOn(): array
    {
        $conversation = Conversation::select('id')->find($this->message->conversation_id);

        $userChannels = [];
        if ($conversation) {
            $participantIds = $conversation->activeParticipants()->pluck('users.id');
            $userChannels = $participantIds
                ->map(fn (int $id) => new PrivateChannel('user.'.$id))
                ->all();
        }

        return [
            new PrivateChannel('conversation.'.$this->message->conversation_id),
            ...$userChannels,
        ];
    }

    public function broadcastWith(): array
    {
        $sender = $this->message->sender;

        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'sender_id' => $this->message->sender_id,
                'sender' => $sender ? [
                    'id' => $sender->id,
                    'name' => $sender->name,
                    'avatar' => $sender->avatar,
                ] : [
                    'id' => 0,
                    'name' => 'Deleted User',
                    'avatar' => null,
                ],
                'type' => $this->message->type,
                'body' => $this->message->body,
                'metadata' => $this->message->metadata,
                'parent_id' => $this->message->parent_id,
                'created_at' => $this->message->created_at->toISOString(),
                'reactions' => [],
            ],
        ];
    }
}
