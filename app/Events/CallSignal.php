<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallSignal implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $callId,
        public int $fromUserId,
        public string $type,
        public array $payload,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('call.'.$this->callId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'call_id' => $this->callId,
            'from_user_id' => $this->fromUserId,
            'type' => $this->type,
            'payload' => $this->payload,
        ];
    }
}
