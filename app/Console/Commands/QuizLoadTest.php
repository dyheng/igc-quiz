<?php

namespace App\Console\Commands;

use App\Events\AnswerSubmitted;
use App\Events\ParticipantJoined;
use App\Models\Participant;
use App\Models\ParticipantAnswer;
use App\Models\Quiz;
use App\Models\QuizSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Simulasi concurrent users: join waiting room dan/atau menjawab quiz.
 *
 * Fase:
 *   join    - Buat N peserta, broadcast ParticipantJoined (untuk test waiting room)
 *   answer  - Simulasi N peserta menjawab semua soal secara paralel
 *   full    - join → tunggu admin start → answer (loop poll status sesi)
 *
 * Usage:
 *   php artisan quiz:loadtest {code} --phase=join  --users=100
 *   php artisan quiz:loadtest {code} --phase=answer --users=100
 *   php artisan quiz:loadtest {code} --phase=full  --users=50
 *   php artisan quiz:loadtest {code} --phase=join  --users=50 --clean
 */
class QuizLoadTest extends Command
{
    protected $signature = 'quiz:loadtest
                            {code : Kode sesi quiz (dari kolom quiz_sessions.code)}
                            {--phase=full  : Fase simulasi: join | answer | full}
                            {--users=50   : Jumlah simulasi peserta}
                            {--delay=0    : Delay antar jawaban dalam millisecond (0 = secepat mungkin)}
                            {--clean      : Hapus semua peserta test setelah selesai}
                            {--no-broadcast : Skip broadcasting (isolasi test DB saja)}';

    protected $description = 'Simulasi concurrent users join waiting room dan/atau menjawab quiz';

    private int $userCount;
    private int $delayMs;
    private bool $noBroadcast;

    public function handle(): int
    {
        if (! extension_loaded('pcntl')) {
            $this->error('Ekstensi pcntl tidak tersedia. Install php-pcntl dan coba lagi.');
            return 1;
        }

        $session = QuizSession::where('code', $this->argument('code'))
            ->with(['quiz.questions.options'])
            ->first();

        if (! $session) {
            $this->error("Sesi dengan kode '{$this->argument('code')}' tidak ditemukan.");
            return 1;
        }

        $this->userCount   = (int) $this->option('users');
        $this->delayMs     = (int) $this->option('delay');
        $this->noBroadcast = (bool) $this->option('no-broadcast');
        $phase             = $this->option('phase');

        $this->printHeader($session, $phase);

        $participantIds = match ($phase) {
            'join'   => $this->runJoinPhase($session),
            'answer' => $this->runAnswerPhase($session),
            'full'   => $this->runFullPhase($session),
            default  => $this->error("Phase tidak valid. Gunakan: join | answer | full") ?: [],
        };

        if (! empty($participantIds) && $this->option('clean')) {
            $this->line('');
            $this->info('Membersihkan data test...');
            Participant::whereIn('id', $participantIds)->delete();
            $this->info('Data test dihapus.');
        } elseif (! empty($participantIds)) {
            $this->line('');
            $this->comment('Tip: gunakan --clean untuk hapus data test otomatis, atau:');
            $this->comment("  mysql> DELETE FROM participants WHERE name LIKE 'LoadTest-%';");
        }

        return 0;
    }

    // ─── FASE JOIN ────────────────────────────────────────────────────────────

    /**
     * Simulasi N peserta masuk waiting room secara bersamaan.
     * Session boleh dalam status 'waiting' maupun 'running'.
     */
    private function runJoinPhase(QuizSession $session): array
    {
        $this->info("📋 Fase JOIN: {$this->userCount} peserta masuk waiting room secara paralel...");

        // Buat participant records di parent sebelum fork
        $participants = $this->createParticipants($session);
        $participantIds = $participants->pluck('id')->toArray();

        $this->info("Peserta dibuat ({$this->userCount}). Memulai broadcast paralel...");
        $startTime = microtime(true);

        $this->forkAndRun($participantIds, function (int $participantId) {
            $participant = Participant::find($participantId);
            if (! $participant) return;

            if (! $this->noBroadcast) {
                broadcast(new ParticipantJoined($participant));
            }
        });

        $elapsed = round(microtime(true) - $startTime, 2);

        $this->line('');
        $this->info("=== Hasil JOIN ===");
        $this->table(['Metrik', 'Nilai'], [
            ['Total waktu',     "{$elapsed}s"],
            ['Peserta join',    count($participantIds)],
            ['Throughput',      round(count($participantIds) / max($elapsed, 0.01), 1) . ' joins/detik'],
            ['Broadcast',       $this->noBroadcast ? 'SKIP' : 'ON'],
        ]);

        $this->info("✓ Admin dashboard seharusnya menampilkan {$this->userCount} peserta secara real-time.");

        return $participantIds;
    }

    // ─── FASE ANSWER ──────────────────────────────────────────────────────────

    /**
     * Simulasi N peserta menjawab semua soal secara paralel.
     * Session harus dalam status 'running'.
     */
    private function runAnswerPhase(QuizSession $session): array
    {
        if (! $session->isRunning()) {
            $this->warn("Status sesi: {$session->status}. Quiz harus dalam status 'running'.");
            if (! $this->confirm('Lanjutkan tetap?')) {
                return [];
            }
        }

        $totalQ = $session->quiz->questions->count();
        if ($totalQ === 0) {
            $this->error('Quiz tidak punya soal.');
            return [];
        }

        $this->info("📝 Fase ANSWER: {$this->userCount} peserta menjawab {$totalQ} soal secara paralel...");

        $participants = $this->createParticipants($session);
        $participantIds = $participants->pluck('id')->toArray();

        $startTime = microtime(true);

        $this->forkAndRun($participantIds, function (int $participantId) {
            $participant = Participant::find($participantId);
            if (! $participant) return;

            $questions = QuizSession::find($participant->quiz_session_id)
                ->quiz
                ->questions()
                ->with('options')
                ->get();

            foreach ($questions as $question) {
                $options = $question->options;
                if ($options->isEmpty()) continue;

                $chosen = $options->random();

                try {
                    ParticipantAnswer::updateOrCreate(
                        [
                            'participant_id' => $participant->id,
                            'question_id'    => $question->id,
                        ],
                        [
                            'question_option_id' => $chosen->id,
                            'is_correct'         => (bool) $chosen->is_correct,
                            'answered_at'        => now(),
                        ]
                    );

                    if (! $this->noBroadcast) {
                        $answered = $participant->answers()->get(['is_correct']);
                        broadcast(new AnswerSubmitted(
                            sessionId:     $participant->quiz_session_id,
                            participantId: $participant->id,
                            correct:       $answered->where('is_correct', true)->count(),
                            wrong:         $answered->where('is_correct', false)->count(),
                            answered:      $answered->count(),
                        ));
                    }
                } catch (\Throwable) {
                    // race condition pada updateOrCreate OK untuk load test
                }

                if ($this->delayMs > 0) {
                    usleep($this->delayMs * 1000);
                }
            }

            $participant->update(['finished_at' => now()]);
        });

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->printAnswerResults($participantIds, $totalQ, $elapsed);

        return $participantIds;
    }

    // ─── FASE FULL ────────────────────────────────────────────────────────────

    /**
     * Simulasi lengkap: join → tunggu admin start → jawab soal.
     * Bisa dipakai untuk mensimulasikan full user journey.
     */
    private function runFullPhase(QuizSession $session): array
    {
        $totalQ = $session->quiz->questions->count();
        if ($totalQ === 0) {
            $this->error('Quiz tidak punya soal.');
            return [];
        }

        // ── Step 1: JOIN ──────────────────────────────────────────────────────
        $this->info("🔄 Fase FULL — Step 1/2: JOIN {$this->userCount} peserta...");
        $participants = $this->createParticipants($session);
        $participantIds = $participants->pluck('id')->toArray();

        $joinStart = microtime(true);
        $this->forkAndRun($participantIds, function (int $participantId) {
            $participant = Participant::find($participantId);
            if ($participant && ! $this->noBroadcast) {
                broadcast(new ParticipantJoined($participant));
            }
        });
        $joinElapsed = round(microtime(true) - $joinStart, 2);
        $this->line("  ✓ {$this->userCount} peserta join dalam {$joinElapsed}s");

        // ── Step 2: TUNGGU SESSION RUNNING ────────────────────────────────────
        $this->info("⏳ Step 2/2: Menunggu admin klik Start Quiz...");
        $this->comment("   (Sekarang buka admin panel dan klik Start Quiz)");
        $this->comment("   Tekan Ctrl+C untuk batalkan.");

        $waited = 0;
        while (true) {
            $session->refresh();
            if ($session->isRunning()) break;
            if ($session->isFinished()) {
                $this->warn("Sesi sudah finished. Batalkan.");
                return $participantIds;
            }
            sleep(2);
            $waited += 2;
            $this->output->write(".");
            if ($waited >= 300) { // max 5 menit
                $this->error("\nTimeout 5 menit. Sesi belum dimulai.");
                return $participantIds;
            }
        }

        $this->line('');
        $this->info("▶ Quiz dimulai! Memulai simulasi jawaban...");

        // ── Step 3: ANSWER ────────────────────────────────────────────────────
        $answerStart = microtime(true);
        $this->forkAndRun($participantIds, function (int $participantId) {
            $participant = Participant::find($participantId);
            if (! $participant) return;

            $questions = QuizSession::find($participant->quiz_session_id)
                ->quiz
                ->questions()
                ->with('options')
                ->get();

            foreach ($questions as $question) {
                $options = $question->options;
                if ($options->isEmpty()) continue;

                $chosen = $options->random();
                try {
                    ParticipantAnswer::updateOrCreate(
                        ['participant_id' => $participant->id, 'question_id' => $question->id],
                        ['question_option_id' => $chosen->id, 'is_correct' => (bool) $chosen->is_correct, 'answered_at' => now()]
                    );

                    if (! $this->noBroadcast) {
                        $answered = $participant->answers()->get(['is_correct']);
                        broadcast(new AnswerSubmitted(
                            sessionId: $participant->quiz_session_id, participantId: $participant->id,
                            correct: $answered->where('is_correct', true)->count(),
                            wrong: $answered->where('is_correct', false)->count(),
                            answered: $answered->count(),
                        ));
                    }
                } catch (\Throwable) {}

                if ($this->delayMs > 0) usleep($this->delayMs * 1000);
            }

            $participant->update(['finished_at' => now()]);
        });

        $answerElapsed = round(microtime(true) - $answerStart, 2);
        $this->printAnswerResults($participantIds, $totalQ, $answerElapsed);

        return $participantIds;
    }

    // ─── HELPERS ─────────────────────────────────────────────────────────────

    /**
     * Buat N participants di parent process sebelum fork.
     */
    private function createParticipants(QuizSession $session): \Illuminate\Support\Collection
    {
        $this->info("Membuat {$this->userCount} peserta test...");
        return collect(range(1, $this->userCount))->map(function ($i) use ($session) {
            return Participant::create([
                'quiz_session_id' => $session->id,
                'name'            => "LoadTest-User-{$i}-" . now()->format('His'),
                'joined_at'       => now(),
            ]);
        });
    }

    /**
     * Fork N child processes dan jalankan callback di setiap child.
     *
     * PENTING: Setelah pcntl_fork(), child process WAJIB reconnect ke DB
     * karena MySQL connection tidak bisa di-share antar proses.
     */
    private function forkAndRun(array $participantIds, callable $callback): void
    {
        // Tutup semua koneksi DB di parent sebelum fork
        // agar child tidak inherit koneksi yang sama
        DB::disconnect();

        $pids = [];

        foreach ($participantIds as $participantId) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->error("Gagal fork process.");
                break;
            }

            if ($pid === 0) {
                // ── CHILD PROCESS ──────────────────────────────────────────
                // Wajib reconnect setelah fork — MySQL connection tidak bisa di-share
                DB::reconnect();

                try {
                    $callback($participantId);
                } catch (\Throwable $e) {
                    // Tulis error ke stderr, jangan crash seluruh test
                    fwrite(STDERR, "Child error (participant {$participantId}): {$e->getMessage()}\n");
                }

                // Child harus exit, bukan return ke parent
                exit(0);
                // ── END CHILD ──────────────────────────────────────────────
            }

            $pids[] = $pid;
        }

        // Reconnect parent setelah fork selesai
        DB::reconnect();

        // Tunggu semua child selesai
        $done = 0;
        $total = count($pids);
        $this->output->write("  Progres: ");
        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
            $done++;
            if ($done % max(1, intdiv($total, 10)) === 0) {
                $pct = round($done / $total * 100);
                $this->output->write("{$pct}% ");
            }
        }
        $this->line('');
    }

    private function printHeader(QuizSession $session, string $phase): void
    {
        $this->info("=== IGC Quiz Load Test ===");
        $this->info("Sesi      : {$session->code} — {$session->quiz->title}");
        $this->info("Status    : {$session->status}");
        $this->info("Phase     : {$phase}");
        $this->info("Users     : {$this->userCount}");
        $this->info("Delay     : {$this->delayMs}ms / jawaban");
        $this->info("Broadcast : " . ($this->noBroadcast ? 'SKIP' : 'ON'));
        $this->line('');
    }

    private function printAnswerResults(array $participantIds, int $totalQ, float $elapsed): void
    {
        $answers  = ParticipantAnswer::whereIn('participant_id', $participantIds)->count();
        $expected = count($participantIds) * $totalQ;
        $finished = Participant::whereIn('id', $participantIds)->whereNotNull('finished_at')->count();
        $successRate = $expected > 0 ? round($answers / $expected * 100, 1) : 0;

        $this->info('');
        $this->info("=== Hasil ANSWER ===");
        $this->table(['Metrik', 'Nilai'], [
            ['Total waktu',      "{$elapsed}s"],
            ['Throughput',       round($answers / max($elapsed, 0.01), 1) . ' jawaban/detik'],
            ['Peserta selesai',  "{$finished} / " . count($participantIds)],
            ['Jawaban tersimpan', "{$answers} / {$expected}"],
            ['Success rate',     "{$successRate}%"],
        ]);

        if ($answers < $expected) {
            $lost = $expected - $answers;
            $this->warn("⚠ {$lost} jawaban hilang — kemungkinan DB bottleneck atau race condition.");
        } else {
            $this->info("✓ Semua jawaban tersimpan dengan sukses.");
        }
    }
}
