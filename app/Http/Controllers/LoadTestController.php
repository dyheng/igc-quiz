<?php

namespace App\Http\Controllers;

use App\Events\AnswerSubmitted;
use App\Events\ParticipantJoined;
use App\Models\Participant;
use App\Models\ParticipantAnswer;
use App\Models\QuizSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint khusus untuk load testing (k6 / artillery / dst).
 * Diproteksi dengan X-Loadtest-Secret header.
 *
 * AKTIFKAN dengan menambahkan di .env:
 *   LOADTEST_SECRET=rahasia-acak-panjang
 *
 * NONAKTIFKAN (production): hapus atau kosongkan LOADTEST_SECRET di .env.
 *
 * JANGAN aktifkan LOADTEST_SECRET di environment production yang live.
 */
class LoadTestController extends Controller
{
    /**
     * POST /loadtest/join/{code}
     * Buat 1 test participant dan broadcast join-nya.
     * k6 akan panggil ini N kali secara paralel.
     */
    public function join(Request $request, string $code): JsonResponse
    {
        $session = QuizSession::where('code', $code)->first();
        if (! $session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        $index = $request->input('index', rand(1000, 9999));
        $participant = Participant::create([
            'quiz_session_id' => $session->id,
            'name'            => "k6-User-{$index}-" . now()->format('His'),
            'joined_at'       => now(),
        ]);

        try {
            broadcast(new ParticipantJoined($participant));
        } catch (\Throwable) {}

        return response()->json([
            'participant_id' => $participant->id,
            'session_id'     => $session->id,
            'name'           => $participant->name,
        ], 201);
    }

    /**
     * POST /loadtest/answer/{code}/{participantId}
     * Submit 1 jawaban acak untuk 1 soal yang ditentukan.
     */
    public function answer(Request $request, string $code, int $participantId): JsonResponse
    {
        $session = QuizSession::where('code', $code)
            ->with(['quiz.questions.options'])
            ->first();

        if (! $session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        $participant = Participant::where('id', $participantId)
            ->where('quiz_session_id', $session->id)
            ->first();

        if (! $participant) {
            return response()->json(['error' => 'Participant not found'], 404);
        }

        $questionId = $request->input('question_id');
        $question = $session->quiz->questions->firstWhere('id', $questionId);

        if (! $question || $question->options->isEmpty()) {
            return response()->json(['error' => 'Question not found'], 404);
        }

        $chosen = $question->options->random();

        ParticipantAnswer::updateOrCreate(
            ['participant_id' => $participant->id, 'question_id' => $question->id],
            ['question_option_id' => $chosen->id, 'is_correct' => (bool) $chosen->is_correct, 'answered_at' => now()]
        );

        try {
            $answered = $participant->answers()->get(['is_correct']);
            broadcast(new AnswerSubmitted(
                sessionId:     $session->id,
                participantId: $participant->id,
                correct:       $answered->where('is_correct', true)->count(),
                wrong:         $answered->where('is_correct', false)->count(),
                answered:      $answered->count(),
            ));
        } catch (\Throwable) {}

        return response()->json(['ok' => true, 'is_correct' => $chosen->is_correct]);
    }

    /**
     * DELETE /loadtest/cleanup/{code}
     * Hapus semua peserta test (nama mengandung "k6-User-").
     */
    public function cleanup(string $code): JsonResponse
    {
        $session = QuizSession::where('code', $code)->first();
        if (! $session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        $deleted = Participant::where('quiz_session_id', $session->id)
            ->where('name', 'LIKE', 'k6-User-%')
            ->delete();

        return response()->json(['deleted' => $deleted]);
    }

    /**
     * GET /loadtest/questions/{code}
     * Ambil daftar question_id untuk dipakai k6.
     */
    public function questions(string $code): JsonResponse
    {
        $session = QuizSession::where('code', $code)
            ->with('quiz.questions')
            ->first();

        if (! $session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        return response()->json([
            'session_id'  => $session->id,
            'status'      => $session->status,
            'question_ids' => $session->quiz->questions->pluck('id'),
        ]);
    }
}
