<?php

namespace App\Livewire;

use App\Models\Quiz;
use App\Models\QuizSession;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Histori Quiz')]
class QuizHistory extends Component
{
    use WithPagination;

    public Quiz $quiz;

    public function mount(Quiz $quiz): void
    {
        $this->quiz = $quiz;
    }

    public function deleteSession(int $id): void
    {
        $session = QuizSession::where('id', $id)
            ->where('quiz_id', $this->quiz->id)
            ->first();

        if (! $session) {
            return;
        }

        $session->delete();

        $this->dispatch('toast', message: 'Sesi dihapus.', variant: 'success');
    }

    public function render()
    {
        $sessions = $this->quiz->sessions()
            ->withCount([
                'participants',
                'participants as finished_count' => fn ($q) => $q->whereNotNull('finished_at'),
            ])
            ->with(['participants' => fn ($q) => $q->withCount([
                'answers as correct_count' => fn ($a) => $a->where('is_correct', true),
                'answers as wrong_count' => fn ($a) => $a->where('is_correct', false),
                'answers as answered_count',
            ])])
            ->latest('id')
            ->paginate(8);

        $totalQuestions = $this->quiz->questions()->count();

        return view('livewire.quiz-history', [
            'sessions' => $sessions,
            'totalQuestions' => $totalQuestions,
        ]);
    }
}
