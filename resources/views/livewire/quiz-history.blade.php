<div class="mx-auto max-w-5xl px-4 md:px-6 py-6 md:py-10">
    <x-ui.toast />

    <div class="mb-6 flex items-start gap-3">
        <a href="{{ route('admin.quizzes.index') }}" wire:navigate class="btn-ghost p-2 mt-0.5" aria-label="Kembali">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M11.78 5.22a.75.75 0 010 1.06L8.06 10l3.72 3.72a.75.75 0 11-1.06 1.06l-4.25-4.25a.75.75 0 010-1.06l4.25-4.25a.75.75 0 011.06 0z" clip-rule="evenodd"/></svg>
        </a>
        <div class="min-w-0 flex-1">
            <p class="label">Histori Quiz</p>
            <h1 class="text-xl md:text-2xl font-semibold tracking-tight text-slate-900 mt-0.5 truncate">{{ $quiz->title }}</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $sessions->total() }} sesi tercatat · {{ $totalQuestions }} soal</p>
        </div>
        <div class="shrink-0 hidden sm:flex items-center gap-2">
            <a href="{{ route('admin.quizzes.preview', $quiz) }}" target="_blank" rel="noopener" class="btn-secondary text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                    <path fill-rule="evenodd" d="M.664 10.59a1.65 1.65 0 010-1.18l.879-.88a11.59 11.59 0 016.456-3.404l.262-.04a8 8 0 011.478 0l.262.04a11.59 11.59 0 016.456 3.404l.879.88a1.65 1.65 0 010 1.18l-.879.88a11.59 11.59 0 01-6.456 3.404l-.262.04a8 8 0 01-1.478 0l-.262-.04A11.59 11.59 0 011.543 11.47l-.879-.88zM10 14a4 4 0 100-8 4 4 0 000 8z" clip-rule="evenodd" />
                </svg>
                <span>Preview Soal</span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3 text-slate-400">
                    <path fill-rule="evenodd" d="M4.25 5.5a.75.75 0 00-.75.75v8.5c0 .414.336.75.75.75h8.5a.75.75 0 00.75-.75v-4a.75.75 0 011.5 0v4A2.25 2.25 0 0112.75 17h-8.5A2.25 2.25 0 012 14.75v-8.5A2.25 2.25 0 014.25 4h5a.75.75 0 010 1.5h-5z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M6.194 12.753a.75.75 0 001.06.053L16.5 4.44v2.81a.75.75 0 001.5 0v-4.5a.75.75 0 00-.75-.75h-4.5a.75.75 0 000 1.5h2.553l-9.056 8.194a.75.75 0 00-.053 1.06z" clip-rule="evenodd" />
                </svg>
            </a>
        </div>
    </div>

    <div class="mb-4 sm:hidden">
        <a href="{{ route('admin.quizzes.preview', $quiz) }}" target="_blank" rel="noopener" class="btn-secondary text-sm w-full justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                <path fill-rule="evenodd" d="M.664 10.59a1.65 1.65 0 010-1.18l.879-.88a11.59 11.59 0 016.456-3.404l.262-.04a8 8 0 011.478 0l.262.04a11.59 11.59 0 016.456 3.404l.879.88a1.65 1.65 0 010 1.18l-.879.88a11.59 11.59 0 01-6.456 3.404l-.262.04a8 8 0 01-1.478 0l-.262-.04A11.59 11.59 0 011.543 11.47l-.879-.88zM10 14a4 4 0 100-8 4 4 0 000 8z" clip-rule="evenodd" />
            </svg>
            <span>Preview Soal &amp; Jawaban</span>
        </a>
    </div>

    @if ($sessions->isEmpty())
        <x-ui.empty-state
            title="Belum ada histori"
            description="Quiz ini belum pernah dimulai. Histori akan muncul setelah Anda memulai sesi melalui tombol Prepare Quiz."
        />
    @else
        <div class="space-y-3">
            @foreach ($sessions as $session)
                @php
                    $statusBadge = match ($session->status) {
                        \App\Models\QuizSession::STATUS_WAITING => ['variant' => 'warning', 'label' => 'Menunggu'],
                        \App\Models\QuizSession::STATUS_RUNNING => ['variant' => 'success', 'label' => 'Berjalan'],
                        default => ['variant' => 'slate', 'label' => 'Selesai'],
                    };
                    $duration = null;
                    if ($session->started_at) {
                        $end = $session->ended_at ?? $session->ends_at ?? now();
                        $duration = $session->started_at->diff($end);
                    }
                @endphp
                <x-ui.card class="break-inside-avoid" padding="false">
                    <div x-data="{ open: false }" wire:key="session-{{ $session->id }}">
                        <div class="px-5 md:px-6 py-4 flex flex-col sm:flex-row sm:items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <x-ui.badge :variant="$statusBadge['variant']">{{ $statusBadge['label'] }}</x-ui.badge>
                                    <span class="font-mono text-xs text-slate-500">{{ $session->code }}</span>
                                </div>
                                <p class="text-sm text-slate-600 mt-1.5">
                                    Dibuat {{ $session->created_at->translatedFormat('d M Y, H:i') }}
                                    @if ($session->started_at)
                                        · Dimulai {{ $session->started_at->translatedFormat('H:i') }}
                                    @endif
                                    @if ($session->ended_at)
                                        · Selesai {{ $session->ended_at->translatedFormat('H:i') }}
                                    @endif
                                    @if ($duration)
                                        · Durasi {{ $duration->h ? $duration->h . 'j ' : '' }}{{ $duration->i }}m
                                    @endif
                                </p>
                                <div class="mt-2 flex items-center gap-4 text-xs text-slate-600">
                                    <span class="inline-flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5 text-slate-400"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zM6 8a2 2 0 11-4 0 2 2 0 014 0zM1.49 15.326a.78.78 0 01-.358-.442 3 3 0 014.308-3.516 6.484 6.484 0 00-1.905 3.959c-.023.222-.014.442.025.654a4.97 4.97 0 01-2.07-.655zM16.44 15.98a4.97 4.97 0 002.07-.654.78.78 0 00.357-.442 3 3 0 00-4.308-3.517 6.484 6.484 0 011.907 3.96 2.32 2.32 0 01-.026.654zM18 8a2 2 0 11-4 0 2 2 0 014 0zM5.304 16.19a.844.844 0 01-.277-.71 5 5 0 019.947 0 .843.843 0 01-.277.71A6.975 6.975 0 0110 18a6.974 6.974 0 01-4.696-1.81z"/></svg>
                                        {{ $session->participants_count }} peserta
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5 text-emerald-500"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                                        {{ $session->finished_count }} selesai
                                    </span>
                                </div>
                            </div>
                            <div class="shrink-0 flex items-center gap-2">
                                @if ($session->participants_count > 0)
                                    <button type="button" x-on:click="open = !open" class="btn-ghost text-sm">
                                        <span x-text="open ? 'Sembunyikan' : 'Lihat peserta'"></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 transition-transform" x-bind:class="open ? 'rotate-180' : ''">
                                            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                @endif
                                @if (! $session->isFinished())
                                    <a href="{{ route('admin.sessions.show', $session) }}" class="btn-secondary text-sm" wire:navigate>Buka Sesi</a>
                                @endif
                                <button
                                    type="button"
                                    wire:click="deleteSession({{ $session->id }})"
                                    wire:confirm="Hapus sesi ini? Seluruh peserta dan jawabannya akan terhapus permanen."
                                    wire:loading.attr="disabled"
                                    wire:target="deleteSession({{ $session->id }})"
                                    class="btn-ghost p-2 text-slate-400 hover:text-rose-600 disabled:opacity-50"
                                    aria-label="Hapus sesi"
                                >
                                    <svg wire:loading.remove wire:target="deleteSession({{ $session->id }})" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5z" clip-rule="evenodd"/></svg>
                                    <svg wire:loading wire:target="deleteSession({{ $session->id }})" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="animate-spin w-4 h-4">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
                                        <path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" class="opacity-75"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        @if ($session->participants_count > 0)
                            <div x-show="open" x-cloak x-collapse class="border-t border-slate-100">
                                <ul class="divide-y divide-slate-100">
                                    @foreach ($session->participants->sortByDesc('correct_count') as $p)
                                        @php
                                            $score = $totalQuestions > 0 ? (int) round(($p->correct_count / $totalQuestions) * 100) : 0;
                                            $unanswered = max(0, $totalQuestions - $p->answered_count);
                                        @endphp
                                        <li class="px-5 md:px-6 py-3 flex items-center gap-3">
                                            <div class="shrink-0 w-9 h-9 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-sm font-medium">
                                                {{ mb_strtoupper(mb_substr($p->name, 0, 1)) }}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <p class="text-sm font-medium text-slate-900 truncate">{{ $p->name }}</p>
                                                    @if ($p->finished_at)
                                                        <x-ui.badge variant="success">Selesai</x-ui.badge>
                                                    @endif
                                                </div>
                                                <div class="mt-1 flex items-center gap-3 text-xs text-slate-600">
                                                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> {{ $p->correct_count }} benar</span>
                                                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-rose-500"></span> {{ $p->wrong_count }} salah</span>
                                                    <span class="text-slate-400">{{ $unanswered }} kosong</span>
                                                </div>
                                            </div>
                                            <div class="shrink-0 text-right">
                                                <p class="text-lg font-semibold tracking-tight text-slate-900">{{ $score }}<span class="text-xs text-slate-400">/100</span></p>
                                                <p class="text-[11px] text-slate-500">{{ $p->correct_count }}/{{ $totalQuestions }}</p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $sessions->links() }}
        </div>
    @endif

</div>
