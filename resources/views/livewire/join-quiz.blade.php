<div class="min-h-[calc(100vh-8rem)] flex items-center justify-center px-4 py-10">
    <x-ui.card class="w-full max-w-sm">
        <div class="flex flex-col items-center text-center">
            <img src="{{ asset('images/logo.png') }}" alt="IGC" class="h-14 md:h-16 w-auto mb-4">
            <p class="label">Quiz</p>
            <h1 class="text-xl md:text-2xl font-semibold tracking-tight text-slate-900 mt-1">{{ $session->quiz->title }}</h1>

            @if ($session->isWaiting())
                <p class="text-sm text-slate-500 mt-2">Masukkan nama Anda untuk bergabung.</p>
            @elseif ($session->isRunning())
                <p class="text-sm text-rose-600 mt-2">Sesi sudah dimulai. Tidak menerima peserta baru.</p>
            @else
                <p class="text-sm text-slate-500 mt-2">Sesi sudah berakhir.</p>
            @endif
        </div>

        @if ($session->isWaiting())
            <form wire:submit="join" class="mt-6 space-y-4">
                <x-ui.input
                    label="Nama"
                    name="name"
                    wire:model="name"
                    autocomplete="name"
                    autofocus
                    placeholder="Nama lengkap"
                    :error="$errors->first('name')"
                />
                <x-ui.button type="submit" loading-target="join" class="w-full btn-lg">
                    <span wire:loading.remove wire:target="join">Gabung</span>
                    <span wire:loading wire:target="join">Bergabung…</span>
                </x-ui.button>
            </form>
        @endif
    </x-ui.card>
</div>
