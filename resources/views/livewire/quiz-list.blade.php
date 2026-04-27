<div class="mx-auto max-w-6xl px-4 md:px-6 py-6 md:py-10">
    <x-ui.toast />

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
        <div>
            <p class="label">Admin</p>
            <h1 class="text-2xl md:text-3xl font-semibold tracking-tight text-slate-900 mt-1">Daftar Quiz</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola quiz, tambahkan pertanyaan, dan jalankan sesi peserta.</p>
        </div>
        <x-ui.button as="a" :href="route('admin.quizzes.create')" class="hidden sm:inline-flex">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
            <span>Tambah Quiz</span>
        </x-ui.button>
    </div>

    @if ($quizzes->isEmpty())
        <x-ui.empty-state
            title="Belum ada quiz"
            description="Tambahkan quiz pertama Anda dan isi pertanyaannya satu per satu."
        >
            <x-slot:action>
                <x-ui.button as="a" :href="route('admin.quizzes.create')">
                    Tambah Quiz Pertama
                </x-ui.button>
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($quizzes as $quiz)
                <x-ui.card class="flex flex-col" wire:key="quiz-{{ $quiz->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-base md:text-lg font-semibold text-slate-900 truncate">{{ $quiz->title }}</h3>
                            <p class="mt-1">
                                <x-ui.badge variant="brand">{{ $quiz->questions_count }} soal</x-ui.badge>
                            </p>
                        </div>
                        <div x-data="{ open: false }" class="relative shrink-0">
                            <button type="button" x-on:click="open = !open" class="btn-ghost p-2" aria-label="Aksi">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"/></svg>
                            </button>
                            <div
                                x-show="open"
                                x-cloak
                                x-on:click.outside="open = false"
                                x-transition.opacity
                                class="absolute right-0 mt-2 w-44 card p-1 z-10"
                            >
                                <a href="{{ route('admin.quizzes.edit', $quiz) }}" wire:navigate class="block px-3 py-2 text-sm rounded-lg hover:bg-slate-100">Edit</a>
                                <a href="{{ route('admin.quizzes.history', $quiz) }}" wire:navigate class="block px-3 py-2 text-sm rounded-lg hover:bg-slate-100">Histori</a>
                                <a href="{{ route('admin.quizzes.preview', $quiz) }}" target="_blank" rel="noopener" class="block px-3 py-2 text-sm rounded-lg hover:bg-slate-100">Preview</a>
                                <div class="my-1 border-t border-slate-100"></div>
                                <button type="button" wire:click="confirmDelete({{ $quiz->id }})" x-on:click="open = false" class="w-full text-left block px-3 py-2 text-sm text-rose-600 rounded-lg hover:bg-rose-50">Hapus</button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex items-center gap-2">
                        <form method="POST" action="{{ route('admin.quizzes.prepare', $quiz) }}" class="flex-1">
                            @csrf
                            <x-ui.button type="submit" class="w-full" :variant="$quiz->questions_count === 0 ? 'secondary' : 'primary'" :disabled="$quiz->questions_count === 0">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M2 10a8 8 0 1116 0 8 8 0 01-16 0zm6.39-2.908a.75.75 0 01.766.027l3.5 2.25a.75.75 0 010 1.262l-3.5 2.25A.75.75 0 018 12.25v-4.5a.75.75 0 01.39-.658z" clip-rule="evenodd"/></svg>
                                <span>Prepare Quiz</span>
                            </x-ui.button>
                        </form>
                    </div>
                    @if ($quiz->questions_count === 0)
                        <p class="text-xs text-slate-500 mt-2">Tambahkan minimal 1 soal sebelum bisa di-prepare.</p>
                    @endif
                </x-ui.card>
            @endforeach
        </div>
    @endif

    {{-- Floating Add (mobile) --}}
    <a href="{{ route('admin.quizzes.create') }}"
        class="sm:hidden fixed bottom-5 right-5 inline-flex items-center justify-center w-14 h-14 rounded-full bg-brand-600 text-white shadow-lg active:bg-brand-800 focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
        aria-label="Tambah Quiz"
        style="bottom: calc(env(safe-area-inset-bottom) + 1.25rem);"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-6 h-6"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
    </a>

    <x-ui.modal name="confirm-delete-quiz" title="Hapus quiz?">
        <p class="text-sm text-slate-600">Quiz beserta seluruh pertanyaan, sesi, dan jawaban peserta akan terhapus permanen.</p>
        <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <x-ui.button variant="ghost" x-on:click="$dispatch('close-modal', 'confirm-delete-quiz')">Batal</x-ui.button>
            <x-ui.button variant="danger" wire:click="deleteConfirmed">Hapus</x-ui.button>
        </div>
    </x-ui.modal>
</div>
