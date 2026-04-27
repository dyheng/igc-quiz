<?php

namespace App\Livewire;

use App\Models\Quiz;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Daftar Quiz')]
class QuizList extends Component
{
    public ?int $confirmingDeleteId = null;

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
        $this->dispatch('open-modal', 'confirm-delete-quiz');
    }

    public function deleteConfirmed(): void
    {
        if ($this->confirmingDeleteId) {
            Quiz::find($this->confirmingDeleteId)?->delete();
            $this->dispatch('toast', message: 'Quiz dihapus.', variant: 'success');
        }
        $this->confirmingDeleteId = null;
        $this->dispatch('close-modal', 'confirm-delete-quiz');
    }

    public function render()
    {
        $quizzes = Quiz::query()
            ->withCount('questions')
            ->orderByDesc('id')
            ->get();

        return view('livewire.quiz-list', [
            'quizzes' => $quizzes,
        ]);
    }
}
