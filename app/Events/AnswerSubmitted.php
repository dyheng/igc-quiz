<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnswerSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $sessionId,
        public int $participantId,
        public int $correct,
        public int $wrong,
        public int $answered,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('session.' . $this->sessionId)];
    }

    public function broadcastAs(): string
    {
        return 'answer.submitted';
    }

    public function broadcastWith(): array
    {
        return [
            'participant_id' => $this->participantId,
            'correct' => $this->correct,
            'wrong' => $this->wrong,
            'answered' => $this->answered,
        ];
    }
}
