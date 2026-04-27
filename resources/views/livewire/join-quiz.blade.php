<div class="min-h-[calc(100vh-8rem)] flex items-center justify-center px-4 py-10"
    x-data="{ resuming: false }"
    x-init="(() => {
        try {
            const code = @js($session->code);
            const key = 'igc-quiz:participant:' + code;
            const params = new URLSearchParams(window.location.search);
            if (params.has('reset')) {
                localStorage.removeItem(key);
                params.delete('reset');
                const clean = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                window.history.replaceState({}, '', clean);
                return;
            }
            const raw = localStorage.getItem(key);
            if (!raw) return;
            const data = JSON.parse(raw);
            if (data && data.id && data.expiresAt > Date.now()) {
                resuming = true;
                window.location.replace('/q/' + code + '/play/' + data.id);
            } else {
                localStorage.removeItem(key);
            }
        } catch (e) {}
    })()"
>
    <div x-show="resuming" x-cloak class="text-center">
        <div class="w-12 h-12 mx-auto rounded-full border-4 border-brand-200 border-t-brand-600 animate-spin"></div>
        <p class="mt-4 text-sm text-slate-600">Anda sudah bergabung sebelumnya. Mengarahkan kembali…</p>
    </div>

    <x-ui.card class="w-full max-w-sm" x-show="!resuming">
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

@script
<script>
    // Saat berhasil submit, simpan participant ke localStorage selama 15 menit.
    Livewire.on('participant-joined', (data) => {
        try {
            const payload = Array.isArray(data) ? data[0] : data;
            const code = payload?.code;
            const id = payload?.id;
            const name = payload?.name;
            if (!code || !id) return;
            const ttlMs = 15 * 60 * 1000;
            localStorage.setItem('igc-quiz:participant:' + code, JSON.stringify({
                id, name, expiresAt: Date.now() + ttlMs,
            }));
        } catch (e) {}
    });
</script>
@endscript
