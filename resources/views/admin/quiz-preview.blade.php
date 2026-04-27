<x-layouts::app :title="'Preview – ' . $quiz->title" :hideAdminLogout="true">
    <div x-data="{ showAnswers: false }" class="mx-auto max-w-3xl px-4 md:px-6 py-6 md:py-10 print:py-0 print:px-0 print:max-w-none">
        <div class="flex items-start justify-between gap-3 mb-6 print:mb-4">
            <div class="min-w-0">
                <p class="label">Preview Quiz</p>
                <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-slate-900 mt-1 break-words">{{ $quiz->title }}</h1>
                <p class="text-sm text-slate-500 mt-1">
                    {{ $quiz->questions->count() }} soal ·
                    <span x-text="showAnswers ? 'Jawaban benar ditandai hijau.' : 'Jawaban disembunyikan – klik ikon mata untuk menampilkan.'"></span>
                    Halaman ini hanya untuk admin sebagai bahan pembahasan.
                </p>
            </div>
            <div class="shrink-0 flex items-center gap-2 print:hidden">
                <button
                    type="button"
                    x-on:click="showAnswers = !showAnswers"
                    :aria-pressed="showAnswers.toString()"
                    :class="showAnswers ? 'btn-primary' : 'btn-secondary'"
                    :title="showAnswers ? 'Sembunyikan jawaban' : 'Tampilkan jawaban'"
                >
                    <svg x-show="!showAnswers" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                    <svg x-show="showAnswers" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <span x-text="showAnswers ? 'Sembunyikan' : 'Tampilkan Jawaban'"></span>
                </button>
                <button type="button" onclick="window.print()" class="btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                        <path fill-rule="evenodd" d="M5 2.75A2.75 2.75 0 017.75 0h4.5A2.75 2.75 0 0115 2.75V5h.25A2.75 2.75 0 0118 7.75v4.5A2.75 2.75 0 0115.25 15H15v2.25A2.75 2.75 0 0112.25 20h-4.5A2.75 2.75 0 015 17.25V15h-.25A2.75 2.75 0 012 12.25v-4.5A2.75 2.75 0 014.75 5H5V2.75zm1.5 0V5h7V2.75c0-.69-.56-1.25-1.25-1.25h-4.5c-.69 0-1.25.56-1.25 1.25zm7 12.5v2c0 .69-.56 1.25-1.25 1.25h-4.5c-.69 0-1.25-.56-1.25-1.25v-2h7zm-7-1.5v-1h7v1h-7zm9-1h.75c.69 0 1.25-.56 1.25-1.25v-4.5c0-.69-.56-1.25-1.25-1.25H4.75c-.69 0-1.25.56-1.25 1.25v4.5c0 .69.56 1.25 1.25 1.25H5v-1a1.5 1.5 0 011.5-1.5h7a1.5 1.5 0 011.5 1.5v1z" clip-rule="evenodd" />
                    </svg>
                    <span>Print</span>
                </button>
                <button type="button" onclick="window.close()" class="btn-ghost text-sm" title="Tutup halaman">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                    </svg>
                    <span>Tutup</span>
                </button>
            </div>
        </div>

        @if ($quiz->questions->isEmpty())
            <x-ui.card class="text-center">
                <p class="text-sm text-slate-500">Belum ada pertanyaan.</p>
            </x-ui.card>
        @else
            <ol class="space-y-4 print:space-y-3">
                @foreach ($quiz->questions as $idx => $q)
                    <li>
                        <x-ui.card class="break-inside-avoid">
                            <div class="flex items-start gap-3">
                                <span class="shrink-0 w-8 h-8 rounded-full bg-brand-100 text-brand-700 text-sm font-semibold flex items-center justify-center">
                                    {{ $idx + 1 }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-base md:text-lg font-medium text-slate-900 leading-relaxed break-words">{{ $q->text }}</p>

                                    <ul class="mt-3 space-y-2">
                                        @foreach ($q->options as $i => $opt)
                                            <li
                                                class="flex items-center gap-3 p-2.5 rounded-xl border text-sm @if (! $opt->is_correct) border-slate-200 bg-white text-slate-700 @endif"
                                                @if ($opt->is_correct)
                                                    :class="showAnswers ? 'border-emerald-300 bg-emerald-50 text-emerald-900' : 'border-slate-200 bg-white text-slate-700'"
                                                @endif
                                            >
                                                <span
                                                    class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold @if (! $opt->is_correct) bg-slate-100 text-slate-700 @endif"
                                                    @if ($opt->is_correct)
                                                        :class="showAnswers ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700'"
                                                    @endif
                                                >
                                                    @if ($opt->is_correct)
                                                        <template x-if="showAnswers">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                                            </svg>
                                                        </template>
                                                        <template x-if="!showAnswers">
                                                            <span>{{ chr(65 + $i) }}</span>
                                                        </template>
                                                    @else
                                                        {{ chr(65 + $i) }}
                                                    @endif
                                                </span>
                                                <span class="flex-1 break-words">{{ $opt->text }}</span>
                                                @if ($opt->is_correct)
                                                    <span x-show="showAnswers" x-cloak class="badge-success print:bg-emerald-100">Jawaban benar</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </x-ui.card>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>

    <style>
        @media print {
            header, footer, #app-progress-bar { display: none !important; }
            body { background: white !important; }
            .card, .shadow-card, .shadow-soft { box-shadow: none !important; }
        }
    </style>
</x-layouts::app>
