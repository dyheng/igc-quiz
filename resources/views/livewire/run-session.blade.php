<div class="mx-auto max-w-6xl px-4 md:px-6 py-6 md:py-10" wire:poll.30s="refreshDashboard">
    <x-ui.toast />

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.quizzes.index') }}" class="btn-ghost p-2" aria-label="Kembali">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M11.78 5.22a.75.75 0 010 1.06L8.06 10l3.72 3.72a.75.75 0 11-1.06 1.06l-4.25-4.25a.75.75 0 010-1.06l4.25-4.25a.75.75 0 011.06 0z" clip-rule="evenodd"/></svg>
        </a>
        <div class="min-w-0">
            <p class="label">Sesi Quiz</p>
            <h1 class="text-xl md:text-2xl font-semibold tracking-tight text-slate-900 truncate mt-0.5">{{ $session->quiz->title }}</h1>
        </div>
        <div class="ml-auto">
            @if ($session->isWaiting())
                <x-ui.badge variant="warning">Menunggu</x-ui.badge>
            @elseif ($session->isRunning())
                <x-ui.badge variant="success">Berjalan</x-ui.badge>
            @else
                <x-ui.badge variant="slate">Selesai</x-ui.badge>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        {{-- LEFT: Controls --}}
        <div class="lg:col-span-2 space-y-4">
            <x-ui.card>
                <p class="label">Link Peserta</p>
                <p class="mt-1 text-xs text-slate-500">Bagikan link ini ke peserta. Kode sesi: <span class="font-mono font-semibold text-slate-700">{{ $session->code }}</span></p>
                <div class="mt-3 flex gap-2" x-data="{ copied: false, url: @js($this->joinUrl) }">
                    <input type="text" readonly :value="url" class="flex-1 font-mono text-sm" x-on:focus="$el.select()">
                    <button type="button" class="btn-secondary" x-on:click="navigator.clipboard.writeText(url); copied = true; setTimeout(() => copied = false, 1500)">
                        <span x-show="!copied">Salin</span>
                        <span x-show="copied" x-cloak>Tersalin!</span>
                    </button>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100">
                    <a href="{{ route('admin.quizzes.preview', $session->quiz) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm font-medium text-brand-700 hover:text-brand-800 hover:underline">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                            <path fill-rule="evenodd" d="M.664 10.59a1.65 1.65 0 010-1.18l.879-.88a11.59 11.59 0 016.456-3.404l.262-.04a8 8 0 011.478 0l.262.04a11.59 11.59 0 016.456 3.404l.879.88a1.65 1.65 0 010 1.18l-.879.88a11.59 11.59 0 01-6.456 3.404l-.262.04a8 8 0 01-1.478 0l-.262-.04A11.59 11.59 0 011.543 11.47l-.879-.88zM10 14a4 4 0 100-8 4 4 0 000 8z" clip-rule="evenodd" />
                        </svg>
                        <span>Preview soal &amp; jawaban benar</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5 text-slate-400">
                            <path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 00-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 00.75-.75v-4a.75.75 0 011.5 0v4A2.25 2.25 0 0112.75 17h-8.5A2.25 2.25 0 012 14.75v-8.5A2.25 2.25 0 014.25 4h5a.75.75 0 010 1.5h-5z" clip-rule="evenodd" />
                            <path fill-rule="evenodd" d="M6.194 12.753a.75.75 0 001.06.053L16.5 4.44v2.81a.75.75 0 001.5 0v-4.5a.75.75 0 00-.75-.75h-4.5a.75.75 0 000 1.5h2.553l-9.056 8.194a.75.75 0 00-.053 1.06z" clip-rule="evenodd" />
                        </svg>
                    </a>
                    <p class="mt-1 text-xs text-slate-500">Buka di tab baru untuk pembahasan setelah quiz selesai.</p>
                </div>
            </x-ui.card>

            <x-ui.card>
                <p class="label">Durasi (menit)</p>
                <div class="mt-2 flex items-center gap-2">
                    <input type="number" wire:model="durationMinutes" min="1" max="240" class="w-28" @disabled(! $session->isWaiting())>
                    <span class="text-sm text-slate-500">menit</span>
                </div>
                @error('durationMinutes') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror

                <div class="mt-5">
                    @if ($session->isWaiting())
                        <x-ui.button wire:click="startQuiz" class="w-full btn-lg" :disabled="$participants->isEmpty()">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M2 10a8 8 0 1116 0 8 8 0 01-16 0zm6.39-2.908a.75.75 0 01.766.027l3.5 2.25a.75.75 0 010 1.262l-3.5 2.25A.75.75 0 018 12.25v-4.5a.75.75 0 01.39-.658z" clip-rule="evenodd"/></svg>
                            <span wire:loading.remove wire:target="startQuiz">Start Quiz</span>
                            <span wire:loading wire:target="startQuiz">Memulai…</span>
                        </x-ui.button>
                        @if ($participants->isEmpty())
                            <p class="text-xs text-slate-500 mt-2 text-center">Tunggu minimal 1 peserta bergabung.</p>
                        @endif
                    @elseif ($session->isRunning())
                        <x-ui.button variant="danger" x-on:click="$dispatch('open-modal', 'confirm-stop-quiz')" class="w-full btn-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M4.25 2A2.25 2.25 0 002 4.25v11.5A2.25 2.25 0 004.25 18h11.5A2.25 2.25 0 0018 15.75V4.25A2.25 2.25 0 0015.75 2H4.25z" clip-rule="evenodd"/></svg>
                            <span>Stop Quiz</span>
                        </x-ui.button>
                    @else
                        <x-ui.button as="a" :href="route('admin.quizzes.index')" variant="secondary" class="w-full">Kembali ke Daftar Quiz</x-ui.button>
                    @endif
                </div>
            </x-ui.card>

            @if ($session->isRunning() && $session->ends_at)
                <x-ui.card>
                    <p class="label">Sisa Waktu</p>
                    <div
                        x-data="countdown(@js($session->ends_at->toIso8601String()))"
                        x-init="start()"
                        x-on:countdown-finished.window="$wire.autoStop()"
                        class="mt-2"
                    >
                        <p class="font-mono tabular-nums text-3xl md:text-4xl font-semibold tracking-tight" :class="{ 'text-rose-600': remaining < 10000, 'text-amber-600': remaining < 30000 && remaining >= 10000 }">
                            <span x-text="formatted"></span>
                        </p>
                        <p class="mt-2 text-xs text-slate-500">Quiz akan otomatis berakhir saat waktu habis.</p>
                    </div>
                </x-ui.card>
            @endif
        </div>

        {{-- RIGHT: Participants --}}
        <div class="lg:col-span-3">
            <x-ui.card padding="false">
                <div class="px-5 md:px-6 py-4 border-b border-slate-200 flex items-center justify-between sticky top-14 md:top-16 bg-white z-10 rounded-t-2xl">
                    <h2 class="text-base md:text-lg font-medium text-slate-900">Peserta</h2>
                    <span aria-live="polite" class="badge-brand">{{ $participants->count() }} bergabung</span>
                </div>

                @if ($participants->isEmpty())
                    <div class="p-8 md:p-10 text-center">
                        <p class="text-sm text-slate-500">Belum ada peserta yang bergabung.</p>
                        <p class="text-xs text-slate-400 mt-1">Bagikan link di samping untuk mulai.</p>
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($participants as $p)
                            <li class="p-4 md:p-5 flex items-center gap-3" wire:key="p-{{ $p->id }}">
                                <div class="shrink-0 w-10 h-10 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-sm font-medium">
                                    {{ mb_strtoupper(mb_substr($p->name, 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-slate-900 truncate">{{ $p->name }}</p>
                                        @if ($p->finished)
                                            <x-ui.badge variant="success">Selesai</x-ui.badge>
                                        @endif
                                    </div>
                                    @if ($session->isWaiting())
                                        <p class="text-xs text-slate-500 mt-0.5">Menunggu start…</p>
                                    @else
                                        <div class="mt-1.5 flex items-center gap-3 text-xs text-slate-600">
                                            <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> {{ $p->correct }} benar</span>
                                            <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span> {{ $p->wrong }} salah</span>
                                            <span class="text-slate-400">{{ $p->answered }}/{{ $totalQuestions }} dijawab</span>
                                        </div>
                                        @if ($totalQuestions > 0)
                                            <div class="mt-2 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                                <div class="h-full bg-brand-500 transition-all" style="width: {{ min(100, ($p->answered / $totalQuestions) * 100) }}%"></div>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>
    </div>

    <x-ui.modal name="confirm-stop-quiz" title="Stop quiz sekarang?">
        <p class="text-sm text-slate-600">Peserta yang sedang menjawab akan langsung dihentikan dan diarahkan ke halaman ringkasan.</p>
        <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <x-ui.button variant="ghost" x-on:click="$dispatch('close-modal', 'confirm-stop-quiz')">Batal</x-ui.button>
            <x-ui.button variant="danger" wire:click="stopQuiz" x-on:click="$dispatch('close-modal', 'confirm-stop-quiz')">Stop Quiz</x-ui.button>
        </div>
    </x-ui.modal>
</div>

@script
<script>
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
