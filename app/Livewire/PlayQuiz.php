<?php

namespace App\Livewire;

use App\Events\AnswerSubmitted;
use App\Events\ParticipantFinished;
use App\Models\Participant;
use App\Models\ParticipantAnswer;
use App\Models\QuizSession;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Quiz')]
class PlayQuiz extends Component
{
    public QuizSession $session;
    public Participant $participant;

    public int $currentIndex = 0;

    /** @var array<int, int|null> question_id => option_id */
    public array $answers = [];

    public function mount(string $code, Participant $participant)
    {
        $this->session = QuizSession::where('code', $code)->firstOrFail()
            ->load(['quiz.questions.options']);

        if ($participant->quiz_session_id !== $this->session->id) {
            return $this->redirectRoute('participant.join', ['code' => $code, 'reset' => 1], navigate: false);
        }

        $expected = session('participant_id_' . $this->session->id);
        if ((int) $expected !== $participant->id) {
            return $this->redirectRoute('participant.join', ['code' => $code, 'reset' => 1], navigate: false);
        }

        $this->participant = $participant;

        $this->answers = $this->participant->answers()
            ->pluck('question_option_id', 'question_id')
            ->toArray();
    }

    #[On('echo:session.{session.id},quiz.started')]
    public function onQuizStarted(): void
    {
        $this->session->refresh();
    }

    #[On('echo:session.{session.id},quiz.stopped')]
    public function onQuizStopped(): void
    {
        $this->session->refresh();
    }

    public function refresh(): void
    {
        $this->session->refresh();
    }

    public function selectOption(int $questionId, int $optionId): void
    {
        $this->session->refresh();
        if (! $this->session->isRunning() || $this->session->isExpired() || $this->participant->finished_at) {
            return;
        }

        $question = $this->session->quiz->questions->firstWhere('id', $questionId);
        if (! $question) return;

        $option = $question->options->firstWhere('id', $optionId);
        if (! $option) return;

        ParticipantAnswer::updateOrCreate(
            [
                'participant_id' => $this->participant->id,
                'question_id' => $questionId,
            ],
            [
                'question_option_id' => $optionId,
                'is_correct' => (bool) $option->is_correct,
                'answered_at' => now(),
            ]
        );

        $this->answers[$questionId] = $optionId;

        $answers = $this->participant->answers()->get(['is_correct']);
        broadcast(new AnswerSubmitted(
            sessionId: $this->session->id,
            participantId: $this->participant->id,
            correct: $answers->where('is_correct', true)->count(),
            wrong: $answers->where('is_correct', false)->count(),
            answered: $answers->count(),
        ));
    }

    public function next(): void
    {
        $total = $this->session->quiz->questions->count();
        if ($this->currentIndex < $total - 1) {
            $this->currentIndex++;
        }
    }

    /*
     * [FORWARD-ONLY MODE]
     * Method prev() dinonaktifkan sementara karena peserta tidak boleh
     * mundur untuk mengubah jawaban saat admin menampilkan dashboard real-time.
     * Untuk mengaktifkan kembali: uncomment method ini DAN uncomment blok tombol Prev
     * di resources/views/livewire/play-quiz.blade.php.
     */
    // public function prev(): void
    // {
    //     if ($this->currentIndex > 0) {
    //         $this->currentIndex--;
    //     }
    // }

    public function finish(): void
    {
        if ($this->participant->finished_at) return;

        $this->participant->update(['finished_at' => now()]);
        $this->participant->refresh();

        broadcast(new ParticipantFinished(
            sessionId: $this->session->id,
            participantId: $this->participant->id,
        ));
    }

    public function getStateProperty(): string
    {
        $this->participant->refresh();
        if ($this->participant->finished_at) return 'finished';

        if ($this->session->isRunning() && $this->session->isExpired()) {
            return 'finished';
        }

        return match ($this->session->status) {
            QuizSession::STATUS_WAITING => 'waiting',
            QuizSession::STATUS_RUNNING => 'running',
            QuizSession::STATUS_FINISHED => 'finished',
        };
    }

    public function render()
    {
        $questions = $this->session->quiz->questions;
        $total = $questions->count();
        $current = $questions[$this->currentIndex] ?? null;

        $summary = null;
        if ($this->state === 'finished') {
            $answered = $this->participant->answers()->with('option', 'question')->get();
            $correct = $answered->where('is_correct', true)->count();
            $wrong = $answered->where('is_correct', false)->count();
            $unanswered = max(0, $total - $answered->count());
            $score = $total > 0 ? (int) round(($correct / $total) * 100) : 0;

            $rows = $questions->map(function ($q) use ($answered) {
                $a = $answered->firstWhere('question_id', $q->id);
                $correctOpt = $q->options->firstWhere('is_correct', true);
                return (object) [
                    'id' => $q->id,
                    'text' => $q->text,
                    'options' => $q->options,
                    'chosen_option_id' => $a?->question_option_id,
                    'is_correct' => $a?->is_correct,
                    'correct_option_id' => $correctOpt?->id,
                ];
            });

            $summary = (object) [
                'total' => $total,
                'correct' => $correct,
                'wrong' => $wrong,
                'unanswered' => $unanswered,
                'score' => $score,
                'rows' => $rows,
            ];
        }

        return view('livewire.play-quiz', [
            'questions' => $questions,
            'totalQuestions' => $total,
            'current' => $current,
            'summary' => $summary,
        ]);
    }
}
