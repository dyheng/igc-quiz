<?php

namespace App\Http\Controllers;

use App\Events\ParticipantLeft;
use App\Models\Participant;
use App\Models\QuizSession;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ParticipantLeaveController extends Controller
{
    public function destroy(Request $request, string $code, Participant $participant): Response
    {
        $session = QuizSession::where('code', $code)->first();

        if (! $session || $participant->quiz_session_id !== $session->id) {
            return response()->noContent();
        }

        if (! $session->isWaiting()) {
            return response()->noContent();
        }

        $sessionId = $session->id;
        $participantId = $participant->id;

        $participant->delete();

        $request->session()->forget('participant_id_' . $sessionId);

        broadcast(new ParticipantLeft($sessionId, $participantId));

        return response()->noContent();
    }
}
