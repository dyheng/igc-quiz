<div class="min-h-[calc(100vh-8rem)] flex items-center justify-center px-4 py-10">
    <x-ui.card class="w-full max-w-sm">
        <div class="flex flex-col items-center text-center">
            <img src="{{ asset('images/logo.png') }}" alt="IGC" class="h-16 md:h-20 w-auto mb-4">
            <h1 class="text-xl md:text-2xl font-semibold tracking-tight text-slate-900">Login Admin</h1>
            <p class="text-sm text-slate-500 mt-1">Masukkan password untuk melanjutkan.</p>
        </div>

        <form wire:submit="login" class="mt-6 space-y-4">
            <x-ui.input
                label="Password"
                type="password"
                name="password"
                wire:model="password"
                autocomplete="current-password"
                autofocus
                :error="$errors->first('password')"
            />

            <x-ui.button type="submit" loading-target="login" class="w-full btn-lg">
                <span wire:loading.remove wire:target="login">Masuk</span>
                <span wire:loading wire:target="login">Memproses…</span>
            </x-ui.button>
        </form>
    </x-ui.card>
</div>
