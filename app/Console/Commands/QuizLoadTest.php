<?php

namespace App\Console\Commands;

use App\Events\AnswerSubmitted;
use App\Models\Participant;
use App\Models\ParticipantAnswer;
use App\Models\QuizSession;
use Illuminate\Console\Command;

/**
 * Simulasi concurrent users menjawab quiz secara paralel menggunakan pcntl_fork().
 * Setiap child process merepresentasikan 1 peserta yang menjawab semua soal.
 *
 * Usage:
 *   php artisan quiz:loadtest {SESSION_CODE} --users=50
 *   php artisan quiz:loadtest {SESSION_CODE} --users=100 --delay=50
 *   php artisan quiz:loadtest {SESSION_CODE} --users=50 --clean   (hapus data test setelah selesai)
 */
class QuizLoadTest extends Command
{
    protected $signature = 'quiz:loadtest
                            {code : Kode sesi quiz (dari kolom quiz_sessions.code)}
                            {--users=50 : Jumlah simulasi peserta}
                            {--delay=0 : Delay antar jawaban tiap peserta dalam millisecond (0 = secepat mungkin)}
                            {--clean : Hapus semua peserta test setelah selesai}
                            {--no-broadcast : Skip broadcasting (lebih cepat, test DB saja)}';

    protected $description = 'Simulasi concurrent users menjawab quiz (pcntl_fork based load test)';

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

        if (! $session->isRunning()) {
            $this->warn("Status sesi: {$session->status}. Sebaiknya sesi dalam status 'running' agar simulasi realistis.");
            if (! $this->confirm('Lanjutkan tetap?')) {
                return 0;
            }
        }

        $questions = $session->quiz->questions;
        $totalQ = $questions->count();

        if ($totalQ === 0) {
            $this->error('Quiz tidak punya soal. Tambahkan soal dulu.');
            return 1;
        }

        $userCount = (int) $this->option('users');
        $delayMs   = (int) $this->option('delay');
        $noBroadcast = (bool) $this->option('no-broadcast');

        $this->info("=== IGC Quiz Load Test ===");
        $this->info("Sesi   : {$session->code} — {$session->quiz->title}");
        $this->info("Soal   : {$totalQ}");
        $this->info("Users  : {$userCount}");
        $this->info("Delay  : {$delayMs}ms / jawaban");
        $this->info("Broadcast: " . ($noBroadcast ? 'SKIP' : 'ON'));
        $this->line('');

        // Buat semua test participant sebelum fork
        $this->info("Membuat {$userCount} peserta test...");
        $participantIds = [];

        for ($i = 1; $i <= $userCount; $i++) {
            $p = Participant::create([
                'quiz_session_id' => $session->id,
                'name'            => "LoadTest-User-{$i}",
                'joined_at'       => now(),
            ]);
            $participantIds[] = $p->id;
        }

        $this->info("Peserta dibuat. Memulai simulasi paralel...");
        $this->line('');

        $startTime = microtime(true);
        $pids = [];

        foreach ($participantIds as $participantId) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->error("Gagal fork process.");
                break;
            }

            if ($pid === 0) {
                // === CHILD PROCESS ===
                // Re-query DB karena parent & child tidak share koneksi
                $participant = Participant::find($participantId);
                $childQuestions = QuizSession::find($session->id)
                    ->quiz->questions()->with('options')->get();

                foreach ($childQuestions as $question) {
                    $options = $question->options;
                    if ($options->isEmpty()) continue;

                    // Pilih opsi acak
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

                        if (! $noBroadcast) {
                            $answered = $participant->answers()->get(['is_correct']);
                            broadcast(new AnswerSubmitted(
                                sessionId:     $session->id,
                                participantId: $participant->id,
                                correct:       $answered->where('is_correct', true)->count(),
                                wrong:         $answered->where('is_correct', false)->count(),
                                answered:      $answered->count(),
                            ));
                        }
                    } catch (\Throwable) {
                        // Abaikan error DB individual (race condition OK untuk load test)
                    }

                    if ($delayMs > 0) {
                        usleep($delayMs * 1000);
                    }
                }

                $participant->update(['finished_at' => now()]);
                exit(0);
                // === END CHILD ===
            }

            $pids[] = $pid;
        }

        // Parent: tunggu semua child selesai
        $this->output->write("Menunggu " . count($pids) . " proses paralel");
        $done = 0;
        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
            $done++;
            if ($done % 10 === 0) {
                $this->output->write(".");
            }
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->line('');
        $this->line('');

        // Kumpulkan hasil
        $answers = ParticipantAnswer::whereIn('participant_id', $participantIds)->count();
        $expected = $userCount * $totalQ;
        $successRate = $expected > 0 ? round($answers / $expected * 100, 1) : 0;
        $finished = Participant::whereIn('id', $participantIds)->whereNotNull('finished_at')->count();

        $this->info("=== Hasil ===");
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Total waktu',          "{$elapsed}s"],
                ['Throughput',           round($answers / $elapsed, 1) . ' jawaban/detik'],
                ['Peserta selesai',      "{$finished} / {$userCount}"],
                ['Jawaban tersimpan',    "{$answers} / {$expected}"],
                ['Success rate',         "{$successRate}%"],
                ['Rata-rata / peserta',  round($elapsed / max(1, $userCount) * 1000, 0) . 'ms (wall-clock bukan serial)'],
            ]
        );

        if ($answers < $expected) {
            $lost = $expected - $answers;
            $this->warn("⚠ {$lost} jawaban hilang (kemungkinan race condition atau DB bottleneck).");
        } else {
            $this->info("✓ Semua jawaban tersimpan dengan sukses.");
        }

        if ((bool) $this->option('clean')) {
            $this->line('');
            $this->info('Membersihkan data test...');
            Participant::whereIn('id', $participantIds)->delete();
            $this->info('Data test dihapus.');
        } else {
            $this->line('');
            $this->comment('Tip: Jalankan dengan --clean untuk hapus data test otomatis,');
            $this->comment('atau hapus manual: DELETE FROM participants WHERE name LIKE "LoadTest-%";');
        }

        return 0;
    }
}
