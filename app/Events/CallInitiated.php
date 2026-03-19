<?php

namespace App\Events;

use App\Models\Call;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallInitiated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Call $call,
    ) {
        $this->call->load(['caller', 'receiver']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->call->conversation_id),
            new PrivateChannel('user.'.$this->call->receiver_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'call' => [
                'id' => $this->call->id,
                'conversation_id' => $this->call->conversation_id,
                'caller' => [
                    'id' => $this->call->caller->id,
                    'name' => $this->call->caller->name,
                    'avatar' => $this->call->caller->avatar,
                ],
                'receiver' => [
                    'id' => $this->call->receiver->id,
                    'name' => $this->call->receiver->name,
                    'avatar' => $this->call->receiver->avatar,
                ],
                'status' => $this->call->status,
            ],
        ];
    }
}
