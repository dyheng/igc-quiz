<div class="mx-auto max-w-2xl px-4 md:px-6 py-6 md:py-10">
    @if ($state === 'waiting')
        {{-- wire:poll.30s hanya sebagai fallback kalau WebSocket gagal. Deteksi start quiz utamanya via echo:quiz.started --}}
        <div class="min-h-[calc(100vh-12rem)] flex flex-col items-center justify-center text-center" wire:poll.30s="refresh">
            <div class="w-14 h-14 rounded-full border-4 border-brand-200 border-t-brand-600 animate-spin"></div>
            <h1 class="mt-6 text-xl md:text-2xl font-semibold tracking-tight text-slate-900">Menunggu admin memulai…</h1>
            <p class="text-sm text-slate-500 mt-2">Halo, <span class="font-medium text-slate-700">{{ $participant->name }}</span>! Quiz akan segera dimulai.</p>
            <p class="text-xs text-slate-400 mt-6">Jangan tutup halaman ini.</p>
        </div>
    @elseif ($state === 'running' && $totalQuestions > 0)
        {{--
            NAVIGASI SEPENUHNYA DI CLIENT-SIDE (Alpine.js).
            Semua soal di-render sekaligus, x-show mengontrol soal mana yang tampil.
            Tidak ada wire:poll dan tidak ada wire:click="next" — nol HTTP request untuk navigasi.
            Hanya selectOption() yang menyentuh server (save jawaban, fire-and-forget).
        --}}
        <div
            wire:key="quiz-running"
            x-data="{
                idx: 0,
                total: {{ $totalQuestions }},
                answers: @js($answers),
                choose(qId, oId) {
                    this.answers[qId] = oId;
                    $wire.selectOption(qId, oId).catch(() => {});
                },
                next() { if (this.idx < this.total - 1) this.idx++; },
                isLast() { return this.idx === this.total - 1; },
            }"
            x-on:countdown-finished.window="$wire.finish()"
        >
            {{-- Sticky header --}}
            <div class="sticky top-14 md:top-16 -mx-4 md:-mx-6 px-4 md:px-6 py-3 bg-white/95 backdrop-blur border-b border-slate-200 z-20">
                <div class="flex items-center justify-between gap-3">
                    <span x-text="(idx + 1) + ' / ' + total" class="badge-brand"></span>
                    @if ($session->ends_at)
                        <div
                            x-data="countdown(@js($session->ends_at->toIso8601String()))"
                            x-init="start()"
                            class="font-mono tabular-nums text-base md:text-lg font-semibold"
                            :class="{ 'text-rose-600': remaining < 10000, 'text-amber-600': remaining < 30000 && remaining >= 10000 }"
                        >
                            <span x-text="formatted"></span>
                        </div>
                    @endif
                </div>
                <div class="mt-2 h-1 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-brand-500 transition-all duration-300" :style="'width: ' + ((idx + 1) / total * 100) + '%'"></div>
                </div>
            </div>

            {{-- Semua soal di-render, Alpine mengontrol mana yang tampil --}}
            @foreach ($questions as $i => $q)
                <div x-show="idx === {{ $i }}" x-cloak wire:key="q-{{ $q->id }}" class="mt-6">
                    <x-ui.card>
                        <p class="label">Pertanyaan {{ $i + 1 }}</p>
                        <h2 class="text-lg md:text-xl font-medium text-slate-900 mt-1.5 leading-relaxed">{{ $q->text }}</h2>

                        <div class="mt-5 space-y-2.5">
                            @foreach ($q->options as $j => $opt)
                                <button
                                    type="button"
                                    wire:key="opt-{{ $opt->id }}"
                                    x-on:click="choose({{ $q->id }}, {{ $opt->id }})"
                                    :class="answers[{{ $q->id }}] === {{ $opt->id }}
                                        ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-200'
                                        : 'border-slate-200 bg-white hover:border-brand-300 hover:bg-slate-50'"
                                    class="w-full text-left flex items-center gap-3 p-3 md:p-4 rounded-xl border transition min-h-14"
                                >
                                    <span
                                        :class="answers[{{ $q->id }}] === {{ $opt->id }} ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-700'"
                                        class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold"
                                    >
                                        {{ chr(65 + $j) }}
                                    </span>
                                    <span class="flex-1 text-sm md:text-base text-slate-900">{{ $opt->text }}</span>
                                    <svg x-show="answers[{{ $q->id }}] === {{ $opt->id }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-brand-600 shrink-0">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            @endforeach
                        </div>
                    </x-ui.card>
                </div>
            @endforeach

            {{-- Bottom action bar --}}
            <div class="sticky bottom-0 -mx-4 md:-mx-6 mt-6 px-4 md:px-6 py-3 bg-white/95 backdrop-blur border-t border-slate-200" style="padding-bottom: calc(env(safe-area-inset-bottom) + 0.75rem);">
                {{--
                    [FORWARD-ONLY MODE]
                    Tombol Prev di-comment agar peserta tidak bisa kembali mengubah jawaban.
                    Untuk mengaktifkan: uncomment <x-ui.button variant="secondary"> di bawah
                    dan bungkus tombol-tombol dalam <div class="flex gap-2">.
                --}}
                {{--
                <x-ui.button variant="secondary" x-on:click="if (idx > 0) idx--" :disabled="idx === 0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M11.78 5.22a.75.75 0 010 1.06L8.06 10l3.72 3.72a.75.75 0 11-1.06 1.06l-4.25-4.25a.75.75 0 010-1.06l4.25-4.25a.75.75 0 011.06 0z" clip-rule="evenodd"/></svg>
                    <span>Prev</span>
                </x-ui.button>
                --}}

                <x-ui.button
                    x-on:click="isLast() ? $dispatch('open-modal', 'confirm-finish') : next()"
                    class="w-full btn-lg"
                >
                    <span x-show="!isLast()" class="flex items-center gap-2">
                        <span>Selanjutnya</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 011.06 0l4.25 4.25a.75.75 0 010 1.06l-4.25 4.25a.75.75 0 11-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 010-1.06z" clip-rule="evenodd"/></svg>
                    </span>
                    <span x-show="isLast()" x-cloak class="flex items-center gap-2">
                        <span>Selesai</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                    </span>
                </x-ui.button>
            </div>

            <x-ui.modal name="confirm-finish" title="Selesaikan quiz?">
                <p class="text-sm text-slate-600">Setelah selesai, Anda tidak bisa mengubah jawaban.</p>
                <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <x-ui.button variant="ghost" x-on:click="$dispatch('close-modal', 'confirm-finish')">Batal</x-ui.button>
                    <x-ui.button wire:click="finish" x-on:click="$dispatch('close-modal', 'confirm-finish')">Ya, Selesai</x-ui.button>
                </div>
            </x-ui.modal>
        </div>
    @elseif ($state === 'finished' && $summary)
        <div>
            <x-ui.card class="text-center">
                <p class="label">Quiz Selesai</p>
                <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-slate-900 mt-2">{{ $participant->name }}</h1>
                <div class="mt-6 grid grid-cols-3 gap-3">
                    <div class="rounded-xl bg-emerald-50 p-3">
                        <p class="text-2xl md:text-3xl font-semibold text-emerald-700">{{ $summary->correct }}</p>
                        <p class="text-xs text-emerald-700/70 mt-1">Benar</p>
                    </div>
                    <div class="rounded-xl bg-rose-50 p-3">
                        <p class="text-2xl md:text-3xl font-semibold text-rose-700">{{ $summary->wrong }}</p>
                        <p class="text-xs text-rose-700/70 mt-1">Salah</p>
                    </div>
                    <div class="rounded-xl bg-slate-100 p-3">
                        <p class="text-2xl md:text-3xl font-semibold text-slate-700">{{ $summary->unanswered }}</p>
                        <p class="text-xs text-slate-500 mt-1">Tdk dijawab</p>
                    </div>
                </div>
                <div class="mt-6">
                    <p class="label">Skor</p>
                    <p class="text-4xl md:text-5xl font-semibold tracking-tight text-brand-700 mt-1">{{ $summary->score }}<span class="text-2xl text-slate-400">/100</span></p>
                </div>
            </x-ui.card>

            <h2 class="text-base md:text-lg font-semibold text-slate-900 mt-8 mb-3">Detail Jawaban</h2>
            <div class="space-y-3">
                @foreach ($summary->rows as $idx => $row)
                    <x-ui.card>
                        <div class="flex items-start gap-3">
                            <span class="shrink-0 w-7 h-7 rounded-full bg-slate-100 text-slate-700 text-sm font-medium flex items-center justify-center">{{ $idx + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm md:text-base text-slate-900">{{ $row->text }}</p>
                                <ul class="mt-3 space-y-1.5">
                                    @foreach ($row->options as $i => $opt)
                                        @php
                                            $isChosen = $row->chosen_option_id === $opt->id;
                                            $isCorrectOpt = $row->correct_option_id === $opt->id;
                                            $cls = 'border-slate-200 bg-white text-slate-700';
                                            if ($isCorrectOpt) $cls = 'border-emerald-300 bg-emerald-50 text-emerald-900';
                                            if ($isChosen && ! $isCorrectOpt) $cls = 'border-rose-300 bg-rose-50 text-rose-900';
                                        @endphp
                                        <li class="flex items-center gap-2 p-2 rounded-lg border text-sm {{ $cls }}">
                                            <span class="shrink-0 w-6 h-6 rounded-full bg-white/60 flex items-center justify-center text-[11px] font-medium">{{ chr(65 + $i) }}</span>
                                            <span class="flex-1">{{ $opt->text }}</span>
                                            @if ($isCorrectOpt)
                                                <span class="badge-success">Benar</span>
                                            @endif
                                            @if ($isChosen && ! $isCorrectOpt)
                                                <span class="badge-danger">Pilihan Anda</span>
                                            @endif
                                        </li>
                                    @endforeach
                                    @if (! $row->chosen_option_id)
                                        <li class="text-xs text-slate-500 italic">Anda tidak menjawab pertanyaan ini.</li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        </div>
    @endif
</div>

@script
<script>
    (function () {
        const leaveUrl = @js(route('participant.leave', ['code' => $session->code, 'participant' => $participant->id]));
        const csrf = @js(csrf_token());
        let sent = false;

        function sendLeave() {
            if (sent) return;
            sent = true;
            try {
                const data = new FormData();
                data.append('_token', csrf);
                if (navigator.sendBeacon) {
                    navigator.sendBeacon(leaveUrl, data);
                } else {
                    fetch(leaveUrl, { method: 'POST', body: data, keepalive: true });
                }
            } catch (e) {}
        }

        window.addEventListener('pagehide', sendLeave);
        window.addEventListener('beforeunload', sendLeave);
    })();

    // Cache identitas peserta selama 15 menit di device.
    // Saat user reload / buka tab baru ke /q/{code}, mereka otomatis diarahkan
    // kembali ke participant ini, tidak bisa daftar dengan nama baru.
    (function () {
        try {
            const code = @js($session->code);
            const key = 'igc-quiz:participant:' + code;
            const ttlMs = 15 * 60 * 1000;
            localStorage.setItem(key, JSON.stringify({
                id: @js($participant->id),
                name: @js($participant->name),
                expiresAt: Date.now() + ttlMs,
            }));
        } catch (e) {}
    })();

    Alpine.data('countdown', (endsAtIso) => ({
        remaining: 0,
        formatted: '00:00',
        timer: null,
        finished: false,
        start() {
            const tick = () => {
                const ms = new Date(endsAtIso).getTime() - Date.now();
                this.remaining = Math.max(0, ms);
                const totalSec = Math.floor(this.remaining / 1000);
                const m = Math.floor(totalSec / 60);
                const s = totalSec % 60;
                this.formatted = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                if (this.remaining <= 0 && !this.finished) {
                    this.finished = true;
                    if (this.timer) clearInterval(this.timer);
                    window.dispatchEvent(new CustomEvent('countdown-finished'));
                }
            };
            tick();
            this.timer = setInterval(tick, 250);
        },
    }));
</script>
@endscript
