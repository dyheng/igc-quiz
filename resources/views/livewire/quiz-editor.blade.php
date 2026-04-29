<div class="mx-auto max-w-6xl px-4 md:px-6 py-6 md:py-10">
    <x-ui.toast />

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.quizzes.index') }}" class="btn-ghost p-2" aria-label="Kembali">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M11.78 5.22a.75.75 0 010 1.06L8.06 10l3.72 3.72a.75.75 0 11-1.06 1.06l-4.25-4.25a.75.75 0 010-1.06l4.25-4.25a.75.75 0 011.06 0z" clip-rule="evenodd"/></svg>
        </a>
        <div class="min-w-0">
            <p class="label">{{ $quiz ? 'Edit Quiz' : 'Quiz Baru' }}</p>
            <h1 class="text-xl md:text-2xl font-semibold tracking-tight text-slate-900 mt-0.5 truncate">
                {{ $quiz?->title ?: 'Quiz tanpa judul' }}
            </h1>
        </div>
        @if ($quiz)
            <span class="ml-auto">
                <x-ui.badge variant="brand">{{ $quiz->questions->count() }} soal</x-ui.badge>
            </span>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        {{-- LEFT: Title + Add question form --}}
        <div class="lg:col-span-3 space-y-6">
            <x-ui.card>
                <h2 class="text-base md:text-lg font-medium text-slate-900">Judul Quiz</h2>
                <form wire:submit="saveTitle" class="mt-3 flex flex-col sm:flex-row gap-2">
                    <input type="text" wire:model="title" placeholder="Misal: Quiz Pengetahuan Umum" class="flex-1" maxlength="200">
                    <x-ui.button type="submit" loading-target="saveTitle">
                        <span wire:loading.remove wire:target="saveTitle">Simpan</span>
                        <span wire:loading wire:target="saveTitle">Menyimpan…</span>
                    </x-ui.button>
                </form>
                @error('title') <p class="text-xs text-rose-600 mt-2">{{ $message }}</p> @enderror
            </x-ui.card>

            @if ($quiz)
                <x-ui.card>
                    <div class="flex items-center justify-between">
                        <h2 class="text-base md:text-lg font-medium text-slate-900">Tambah Pertanyaan</h2>
                        <span class="badge-slate">{{ $quiz->questions->count() }} sudah disimpan</span>
                    </div>

                    <form wire:submit="addQuestion" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Pertanyaan</label>
                            <textarea wire:model="newQuestionText" rows="3" maxlength="1000" placeholder="Tulis pertanyaan…" class="w-full"></textarea>
                            @error('newQuestionText') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

<div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-slate-700">Opsi Jawaban</label>
                                <span class="text-xs text-slate-500">Pilih satu sebagai jawaban benar</span>
                            </div>
                            <div class="space-y-2">
                                @foreach ($newOptions as $i => $opt)
                                    <div wire:key="new-opt-{{ $i }}" class="flex items-center gap-2">
                                        <button type="button" wire:click="setNewCorrect({{ $i }})" class="shrink-0 w-9 h-9 rounded-full border flex items-center justify-center transition {{ $opt['is_correct'] ? 'bg-emerald-600 border-emerald-600 text-white' : 'bg-white border-slate-300 text-slate-400 hover:border-emerald-500' }}" aria-label="Tandai sebagai jawaban benar">
                                            @if ($opt['is_correct'])
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                                            @else
                                                <span class="text-sm font-medium">{{ chr(65 + $i) }}</span>
                                            @endif
                                        </button>
                                        <input type="text" wire:model="newOptions.{{ $i }}.text" placeholder="Opsi {{ chr(65 + $i) }}" class="flex-1" maxlength="500">
                                        @if (count($newOptions) > 2)
                                            <button type="button" wire:click="removeOptionRow({{ $i }})" class="btn-ghost p-2 text-slate-500 hover:text-rose-600" aria-label="Hapus opsi">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                    @error("newOptions.$i.text") <p class="text-xs text-rose-600 ml-11">{{ $message }}</p> @enderror
                                @endforeach
                            </div>
                            @error('newOptions') <p class="text-xs text-rose-600 mt-2">{{ $message }}</p> @enderror

                            @if (count($newOptions) < 6)
                                <button type="button" wire:click="addOptionRow" class="btn-ghost text-sm mt-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                                    <span>Tambah Opsi</span>
                                </button>
                            @endif
                        </div>

                        <div class="flex justify-end">
                            <x-ui.button type="submit" loading-target="addQuestion">
                                <span wire:loading.remove wire:target="addQuestion">Simpan Pertanyaan</span>
                                <span wire:loading wire:target="addQuestion">Menyimpan…</span>
                            </x-ui.button>
                        </div>
                    </form>
                </x-ui.card>
            @endif
        </div>

        {{-- RIGHT: Question list --}}
        <div class="lg:col-span-2">
            <x-ui.card padding="false">
                <div class="px-5 md:px-6 py-4 border-b border-slate-200 flex items-center justify-between sticky top-14 md:top-16 bg-white z-10 rounded-t-2xl">
                    <h2 class="text-base md:text-lg font-medium text-slate-900">Daftar Pertanyaan</h2>
                    @if ($quiz)
                        <span aria-live="polite" class="badge-brand">{{ $quiz->questions->count() }} soal</span>
                    @endif
                </div>

                @if (! $quiz || $quiz->questions->isEmpty())
                    <div class="p-8 md:p-10 text-center">
                        <p class="text-sm text-slate-500">Belum ada pertanyaan. Tambahkan menggunakan form di samping.</p>
                    </div>
                @else
                    <ol class="divide-y divide-slate-100">
                        @foreach ($quiz->questions as $idx => $q)
                            <li wire:key="q-{{ $q->id }}" class="p-4 md:p-5">
                                @if ($editingQuestionId === $q->id)
                                    <form wire:submit="saveEditQuestion" class="space-y-3">
                                        <textarea wire:model="editingQuestionText" rows="2" class="w-full" maxlength="1000"></textarea>
                                        @error('editingQuestionText') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                        <div class="space-y-2">
                                            @foreach ($editingOptions as $i => $opt)
                                                <div wire:key="edit-opt-{{ $q->id }}-{{ $i }}" class="flex items-center gap-2">
                                                    <button type="button" wire:click="setEditingCorrect({{ $i }})" class="shrink-0 w-9 h-9 rounded-full border flex items-center justify-center {{ $opt['is_correct'] ? 'bg-emerald-600 border-emerald-600 text-white' : 'bg-white border-slate-300 text-slate-400' }}">
                                                        @if ($opt['is_correct'])
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                                                        @else
                                                            <span class="text-sm font-medium">{{ chr(65 + $i) }}</span>
                                                        @endif
                                                    </button>
                                                    <input type="text" wire:model="editingOptions.{{ $i }}.text" class="flex-1" maxlength="500">
                                                    @if (count($editingOptions) > 2)
                                                        <button type="button" wire:click="removeEditingOptionRow({{ $i }})" class="btn-ghost p-2 text-slate-500 hover:text-rose-600" aria-label="Hapus opsi">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4z" clip-rule="evenodd"/></svg>
                                                        </button>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                        @error('editingOptions') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                                        @if (count($editingOptions) < 6)
                                            <button type="button" wire:click="addEditingOptionRow" class="btn-ghost text-xs">+ Tambah Opsi</button>
                                        @endif
                                        <div class="flex justify-end gap-2 pt-1">
                                            <x-ui.button type="button" variant="ghost" wire:click="cancelEditQuestion">Batal</x-ui.button>
                                            <x-ui.button type="submit">Simpan</x-ui.button>
                                        </div>
                                    </form>
                                @else
                                    <div class="flex items-start gap-3">
                                        <span class="shrink-0 w-7 h-7 rounded-full bg-slate-100 text-slate-700 text-sm font-medium flex items-center justify-center">{{ $idx + 1 }}</span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-slate-900 break-words">{{ $q->text }}</p>
                                            <ul class="mt-2 space-y-1">
                                                @foreach ($q->options as $opt)
                                                    <li class="flex items-start gap-2 text-sm">
                                                        <span class="shrink-0 mt-0.5 w-5 h-5 rounded-full flex items-center justify-center text-[11px] font-medium {{ $opt->is_correct ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                                            @if ($opt->is_correct)
                                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                                                            @else
                                                                {{ chr(65 + $loop->index) }}
                                                            @endif
                                                        </span>
                                                        <span class="text-slate-700 break-words">{{ $opt->text }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        <div class="shrink-0 flex items-center gap-1">
                                            <button type="button" wire:click="startEditQuestion({{ $q->id }})" class="btn-ghost p-2" aria-label="Edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M2.695 14.762l-1.262 3.155a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.886L17.5 5.501a2.121 2.121 0 00-3-3L3.58 13.419a4 4 0 00-.885 1.343z" /></svg>
                                            </button>
                                            <button type="button" wire:click="deleteQuestion({{ $q->id }})" wire:confirm="Hapus pertanyaan ini?" class="btn-ghost p-2 text-slate-500 hover:text-rose-600" aria-label="Hapus">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5z" clip-rule="evenodd"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-ui.card>
        </div>
    </div>
</div>
