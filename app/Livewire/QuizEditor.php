<?php

namespace App\Livewire;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Editor Quiz')]
class QuizEditor extends Component
{
    public ?Quiz $quiz = null;

    public string $title = '';

    public string $newQuestionText = '';

    /** @var array<int, array{text: string, is_correct: bool}> */
    public array $newOptions = [
        ['text' => '', 'is_correct' => true],
        ['text' => '', 'is_correct' => false],
    ];

    public ?int $editingQuestionId = null;

    public string $editingQuestionText = '';

    /** @var array<int, array{id: ?int, text: string, is_correct: bool}> */
    public array $editingOptions = [];

    protected function validationAttributes(): array
    {
        $attrs = [
            'title' => 'Judul quiz',
            'newQuestionText' => 'Pertanyaan',
            'newOptions' => 'Opsi jawaban',
            'editingQuestionText' => 'Pertanyaan',
            'editingOptions' => 'Opsi jawaban',
        ];

        foreach ($this->newOptions as $i => $opt) {
            $label = chr(65 + $i);
            $attrs["newOptions.$i.text"] = "Opsi $label";
            $attrs["newOptions.$i.is_correct"] = "Tanda jawaban benar opsi $label";
        }

        foreach ($this->editingOptions as $i => $opt) {
            $label = chr(65 + $i);
            $attrs["editingOptions.$i.text"] = "Opsi $label";
            $attrs["editingOptions.$i.is_correct"] = "Tanda jawaban benar opsi $label";
        }

        return $attrs;
    }

    protected function messages(): array
    {
        return [
            'newOptions.min' => 'Minimal 2 opsi jawaban.',
            'newOptions.max' => 'Maksimal 6 opsi jawaban.',
            'editingOptions.min' => 'Minimal 2 opsi jawaban.',
            'editingOptions.max' => 'Maksimal 6 opsi jawaban.',
        ];
    }

    public function mount(?Quiz $quiz = null): void
    {
        if ($quiz && $quiz->exists) {
            $this->quiz = $quiz->loadMissing(['questions.options']);
            $this->title = $quiz->title;
        }
    }

    public function saveTitle(): void
    {
        $data = $this->validate([
            'title' => 'required|string|max:200',
        ]);

        if ($this->quiz) {
            $this->quiz->update($data);
        } else {
            $this->quiz = Quiz::create($data);

            $this->redirectRoute('admin.quizzes.edit', $this->quiz, navigate: true);
            return;
        }

        $this->dispatch('toast', message: 'Judul disimpan.', variant: 'success');
    }

    public function addOptionRow(): void
    {
        if (count($this->newOptions) >= 6) {
            return;
        }
        $this->newOptions[] = ['text' => '', 'is_correct' => false];
    }

    public function removeOptionRow(int $index): void
    {
        if (count($this->newOptions) <= 2) {
            return;
        }

        $wasCorrect = $this->newOptions[$index]['is_correct'] ?? false;
        unset($this->newOptions[$index]);
        $this->newOptions = array_values($this->newOptions);

        if ($wasCorrect && ! collect($this->newOptions)->contains('is_correct', true)) {
            $this->newOptions[0]['is_correct'] = true;
        }
    }

    public function setNewCorrect(int $index): void
    {
        foreach ($this->newOptions as $i => $opt) {
            $this->newOptions[$i]['is_correct'] = ($i === $index);
        }
    }

    public function addQuestion(): void
    {
        if (! $this->quiz) {
            $this->saveTitle();
            return;
        }

        $this->newOptions = array_values($this->newOptions);

        $rules = [
            'newQuestionText' => 'required|string|max:1000',
            'newOptions' => 'array|min:2|max:6',
            'newOptions.*.text' => 'required|string|max:500',
            'newOptions.*.is_correct' => 'boolean',
        ];

        $this->validate($rules);

        $correctCount = collect($this->newOptions)->filter(fn ($o) => ! empty($o['is_correct']))->count();
        if ($correctCount !== 1) {
            $this->addError('newOptions', 'Pilih tepat satu jawaban benar.');
            return;
        }

        DB::transaction(function () {
            $nextOrder = ($this->quiz->questions()->max('order') ?? 0) + 1;

            $question = $this->quiz->questions()->create([
                'text' => $this->newQuestionText,
                'order' => $nextOrder,
            ]);

            foreach ($this->newOptions as $i => $opt) {
                $question->options()->create([
                    'text' => $opt['text'],
                    'is_correct' => (bool) $opt['is_correct'],
                    'order' => $i + 1,
                ]);
            }
        });

        $this->reset(['newQuestionText']);
        $this->newOptions = [
            ['text' => '', 'is_correct' => true],
            ['text' => '', 'is_correct' => false],
        ];

        $this->quiz->load('questions.options');

        $this->dispatch('toast', message: 'Pertanyaan ditambahkan.', variant: 'success');
    }

    public function startEditQuestion(int $questionId): void
    {
        $q = $this->quiz->questions->firstWhere('id', $questionId);
        if (! $q) return;

        $this->editingQuestionId = $q->id;
        $this->editingQuestionText = $q->text;
        $this->editingOptions = $q->options
            ->map(fn ($o) => ['id' => $o->id, 'text' => $o->text, 'is_correct' => (bool) $o->is_correct])
            ->values()
            ->toArray();
    }

    public function cancelEditQuestion(): void
    {
        $this->editingQuestionId = null;
        $this->editingQuestionText = '';
        $this->editingOptions = [];
    }

    public function setEditingCorrect(int $index): void
    {
        foreach ($this->editingOptions as $i => $opt) {
            $this->editingOptions[$i]['is_correct'] = ($i === $index);
        }
    }

    public function addEditingOptionRow(): void
    {
        if (count($this->editingOptions) >= 6) return;
        $this->editingOptions[] = ['id' => null, 'text' => '', 'is_correct' => false];
    }

    public function removeEditingOptionRow(int $index): void
    {
        if (count($this->editingOptions) <= 2) return;
        $wasCorrect = $this->editingOptions[$index]['is_correct'] ?? false;
        unset($this->editingOptions[$index]);
        $this->editingOptions = array_values($this->editingOptions);
        if ($wasCorrect && ! collect($this->editingOptions)->contains('is_correct', true)) {
            $this->editingOptions[0]['is_correct'] = true;
        }
    }

    public function saveEditQuestion(): void
    {
        if (! $this->editingQuestionId) return;

        $this->editingOptions = array_values($this->editingOptions);

        $this->validate([
            'editingQuestionText' => 'required|string|max:1000',
            'editingOptions' => 'array|min:2|max:6',
            'editingOptions.*.text' => 'required|string|max:500',
            'editingOptions.*.is_correct' => 'boolean',
        ]);

        $correctCount = collect($this->editingOptions)->filter(fn ($o) => ! empty($o['is_correct']))->count();
        if ($correctCount !== 1) {
            $this->addError('editingOptions', 'Pilih tepat satu jawaban benar.');
            return;
        }

        $question = Question::find($this->editingQuestionId);
        if (! $question || $question->quiz_id !== $this->quiz->id) {
            return;
        }

        DB::transaction(function () use ($question) {
            $question->update(['text' => $this->editingQuestionText]);

            $existingIds = collect($this->editingOptions)->pluck('id')->filter()->all();

            $question->options()->whereNotIn('id', $existingIds)->delete();

            foreach ($this->editingOptions as $i => $opt) {
                if (! empty($opt['id'])) {
                    QuestionOption::where('id', $opt['id'])->update([
                        'text' => $opt['text'],
                        'is_correct' => (bool) $opt['is_correct'],
                        'order' => $i + 1,
                    ]);
                } else {
                    $question->options()->create([
                        'text' => $opt['text'],
                        'is_correct' => (bool) $opt['is_correct'],
                        'order' => $i + 1,
                    ]);
                }
            }
        });

        $this->cancelEditQuestion();
        $this->quiz->load('questions.options');
        $this->dispatch('toast', message: 'Pertanyaan diperbarui.', variant: 'success');
    }

    public function deleteQuestion(int $questionId): void
    {
        $q = $this->quiz?->questions->firstWhere('id', $questionId);
        if (! $q) return;
        $q->delete();
        $this->quiz->load('questions.options');
        if ($this->editingQuestionId === $questionId) {
            $this->cancelEditQuestion();
        }
        $this->dispatch('toast', message: 'Pertanyaan dihapus.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.quiz-editor');
    }
}
