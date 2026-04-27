<?php

namespace App\Livewire;

use App\Events\QuizStarted;
use App\Events\QuizStopped;
use App\Models\QuizSession;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Sesi Quiz')]
class RunSession extends Component
{
    public QuizSession $session;

    public ?int $durationMinutes = 5;

    public function mount(QuizSession $session): void
    {
        $this->session = $session->load('quiz');
        $this->durationMinutes = $session->duration_minutes ?? 5;
    }

    #[On('echo:session.{session.id},participant.joined')]
    #[On('echo:session.{session.id},participant.left')]
    #[On('echo:session.{session.id},answer.submitted')]
    #[On('echo:session.{session.id},participant.finished')]
    public function refreshDashboard(): void
    {
        $this->session->refresh();
    }

    public function startQuiz(): void
    {
        $this->validate(
            rules: [
                'durationMinutes' => 'required|integer|min:1|max:240',
            ],
            messages: [
                'durationMinutes.required' => 'Durasi quiz wajib diisi.',
                'durationMinutes.integer' => 'Durasi quiz harus berupa angka.',
                'durationMinutes.min' => 'Durasi quiz minimal :min menit.',
                'durationMinutes.max' => 'Durasi quiz maksimal :max menit.',
            ],
            attributes: [
                'durationMinutes' => 'Durasi quiz',
            ],
        );

        if (! $this->session->isWaiting()) {
            return;
        }

        $startedAt = now();
        $endsAt = $startedAt->copy()->addMinutes($this->durationMinutes);

        $this->session->update([
            'duration_minutes' => $this->durationMinutes,
            'status' => QuizSession::STATUS_RUNNING,
            'started_at' => $startedAt,
            'ends_at' => $endsAt,
        ]);

        broadcast(new QuizStarted(
            sessionId: $this->session->id,
            endsAt: $endsAt->toIso8601String(),
        ));
    }

    public function stopQuiz(): void
    {
        if ($this->session->isFinished()) {
            return;
        }

        $this->session->update([
            'status' => QuizSession::STATUS_FINISHED,
            'ended_at' => now(),
        ]);

        broadcast(new QuizStopped(sessionId: $this->session->id));
    }

    public function getJoinUrlProperty(): string
    {
        return route('participant.join', $this->session->code);
    }

    public function render()
    {
        $this->session->load([
            'participants.answers' => fn ($q) => $q->select('id', 'participant_id', 'is_correct'),
            'quiz' => fn ($q) => $q->withCount('questions'),
        ]);

        $totalQuestions = $this->session->quiz?->questions_count ?? 0;

        $participants = $this->session->participants->map(function ($p) use ($totalQuestions) {
            $correct = $p->answers->where('is_correct', true)->count();
            $wrong = $p->answers->where('is_correct', false)->count();
            $answered = $p->answers->count();
            return (object) [
                'id' => $p->id,
                'name' => $p->name,
                'correct' => $correct,
                'wrong' => $wrong,
                'answered' => $answered,
                'unanswered' => max(0, $totalQuestions - $answered),
                'finished' => (bool) $p->finished_at,
            ];
        });

        return view('livewire.run-session', [
            'totalQuestions' => $totalQuestions,
            'participants' => $participants,
        ]);
    }
}
