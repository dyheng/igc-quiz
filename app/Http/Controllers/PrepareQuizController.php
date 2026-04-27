<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizSession;
use Illuminate\Http\Request;

class PrepareQuizController extends Controller
{
    public function store(Request $request, Quiz $quiz)
    {
        if ($quiz->questions()->count() === 0) {
            return back()->with('error', 'Quiz harus memiliki minimal 1 pertanyaan.');
        }

        $session = QuizSession::create([
            'quiz_id' => $quiz->id,
            'status' => QuizSession::STATUS_WAITING,
        ]);

        return redirect()->route('admin.sessions.show', $session);
    }
}
