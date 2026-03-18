<?php

namespace App\Events;

use App\Models\Message;
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
        return [
            new PrivateChannel('conversation.'.$this->message->conversation_id),
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
