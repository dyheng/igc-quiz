<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizStopped implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $sessionId,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('session.' . $this->sessionId)];
    }

    public function broadcastAs(): string
    {
        return 'quiz.stopped';
    }

    public function broadcastWith(): array
    {
        return [];
    }
}
