<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantFinished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $sessionId,
        public int $participantId,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('session.' . $this->sessionId)];
    }

    public function broadcastAs(): string
    {
        return 'participant.finished';
    }

    public function broadcastWith(): array
    {
        return [
            'participant_id' => $this->participantId,
        ];
    }
}
