<?php

namespace App\Livewire;

use App\Events\ParticipantJoined;
use App\Models\Participant;
use App\Models\QuizSession;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Gabung Quiz')]
class JoinQuiz extends Component
{
    public QuizSession $session;

    #[Validate(
        'required|string|min:2|max:60',
        as: 'Nama',
        message: [
            'required' => 'Nama wajib diisi.',
            'min' => 'Nama minimal :min karakter.',
            'max' => 'Nama maksimal :max karakter.',
        ],
    )]
    public string $name = '';

    public function mount(string $code): void
    {
        $this->session = QuizSession::where('code', $code)
            ->with('quiz')
            ->firstOrFail();
    }

    public function join()
    {
        $this->session->refresh();

        if (! $this->session->isWaiting()) {
            $this->addError('name', 'Sesi quiz sudah tidak menerima peserta baru.');
            return;
        }

        $this->validate();

        $participant = Participant::create([
            'quiz_session_id' => $this->session->id,
            'name' => trim($this->name),
            'joined_at' => now(),
        ]);

        session()->put('participant_id_' . $this->session->id, $participant->id);

        broadcast(new ParticipantJoined($participant));

        return $this->redirectRoute('participant.play', [
            'code' => $this->session->code,
            'participant' => $participant->id,
        ], navigate: true);
    }

    public function render()
    {
        return view('livewire.join-quiz');
    }
}
