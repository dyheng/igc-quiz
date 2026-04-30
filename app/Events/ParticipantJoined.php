<?php

namespace App\Events;

use App\Models\Participant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $sessionId;
    public int $participantId;
    public string $name;

    public function __construct(Participant $participant)
    {
        $this->sessionId = $participant->quiz_session_id;
        $this->participantId = $participant->id;
        $this->name = $participant->name;
    }

    public function broadcastOn(): array
    {
        return [new Channel('session.' . $this->sessionId)];
    }

    public function broadcastAs(): string
    {
        return 'participant.joined';
    }

    public function broadcastWith(): array
    {
        return [
            'participant_id' => $this->participantId,
            'name' => $this->name,
        ];
    }
}
