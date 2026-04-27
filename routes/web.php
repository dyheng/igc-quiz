<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ParticipantLeaveController;
use App\Http\Controllers\PrepareQuizController;
use App\Livewire\AdminLogin;
use App\Livewire\JoinQuiz;
use App\Livewire\PlayQuiz;
use App\Livewire\QuizEditor;
use App\Livewire\QuizHistory;
use App\Livewire\QuizList;
use App\Livewire\RunSession;
use App\Models\Quiz;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');

Route::middleware('guest.admin')->group(function () {
    Route::get('/admin/login', AdminLogin::class)->name('admin.login');
});

Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/quizzes', QuizList::class)->name('quizzes.index');
    Route::get('/quizzes/create', QuizEditor::class)->name('quizzes.create');
    Route::get('/quizzes/{quiz}/edit', QuizEditor::class)->name('quizzes.edit');
    Route::get('/quizzes/{quiz}/history', QuizHistory::class)->name('quizzes.history');

    Route::get('/quizzes/{quiz}/preview', function (Quiz $quiz) {
        $quiz->load(['questions' => fn ($q) => $q->orderBy('order'), 'questions.options' => fn ($q) => $q->orderBy('order')]);
        return view('admin.quiz-preview', ['quiz' => $quiz]);
    })->name('quizzes.preview');

    Route::post('/quizzes/{quiz}/prepare', [PrepareQuizController::class, 'store'])->name('quizzes.prepare');

    Route::get('/sessions/{session}', RunSession::class)->name('sessions.show');
});

Route::get('/q/{code}', JoinQuiz::class)->name('participant.join');
Route::get('/q/{code}/play/{participant}', PlayQuiz::class)->name('participant.play');
Route::post('/q/{code}/leave/{participant}', [ParticipantLeaveController::class, 'destroy'])->name('participant.leave');
