<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#374f88">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-slate-50 text-slate-900 antialiased">
    <div id="app-progress-bar" aria-hidden="true" class="fixed top-0 left-0 right-0 h-0.5 bg-brand-500 z-[60] pointer-events-none origin-left" style="transform: scaleX(0); opacity: 0; transition: transform .2s ease-out, opacity .25s ease-out;"></div>

    <div class="min-h-screen flex flex-col" style="padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom);">
        @if (! ($hideNav ?? false))
            <header class="bg-white/90 backdrop-blur border-b border-slate-200 sticky top-0 z-30">
                <div class="mx-auto max-w-6xl px-4 md:px-6 h-14 md:h-16 flex items-center justify-between">
                    <a href="{{ url('/') }}" class="flex items-center gap-3">
                        <img src="{{ asset('images/logo.png') }}" alt="IGC Logo" class="h-8 md:h-10 w-auto">
                        <span class="hidden sm:inline text-sm md:text-base font-semibold text-slate-900 tracking-tight">{{ config('app.name') }}</span>
                    </a>
                    @if (! ($hideAdminLogout ?? false) && session(config('quiz.admin_session_key')))
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="btn-ghost text-sm">
                                <span>Keluar</span>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M3 4.25A2.25 2.25 0 015.25 2h5.5A2.25 2.25 0 0113 4.25v2a.75.75 0 01-1.5 0v-2a.75.75 0 00-.75-.75h-5.5a.75.75 0 00-.75.75v11.5c0 .414.336.75.75.75h5.5a.75.75 0 00.75-.75v-2a.75.75 0 011.5 0v2A2.25 2.25 0 0110.75 18h-5.5A2.25 2.25 0 013 15.75V4.25z" clip-rule="evenodd" /><path fill-rule="evenodd" d="M19 10a.75.75 0 00-.22-.53l-3-3a.75.75 0 10-1.06 1.06l1.72 1.72H9.75a.75.75 0 000 1.5h6.69l-1.72 1.72a.75.75 0 101.06 1.06l3-3A.75.75 0 0019 10z" clip-rule="evenodd" /></svg>
                            </button>
                        </form>
                    @endif
                </div>
            </header>
        @endif

        <main class="flex-1">
            {{ $slot }}
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-6xl px-4 md:px-6 py-4 text-xs text-slate-500 flex items-center justify-between">
                <span>{{ config('app.name') }}</span>
                <span>&copy; {{ date('Y') }}</span>
            </div>
        </footer>
    </div>

    @livewireScripts

    <script>
        (function () {
            const bar = document.getElementById('app-progress-bar');
            if (!bar) return;
            let active = 0;
            let timer = null;
            let width = 0;

            function setWidth(v) {
                width = v;
                bar.style.transform = `scaleX(${v / 100})`;
            }
            function start() {
                if (active === 1) {
                    bar.style.opacity = '1';
                    setWidth(15);
                    clearInterval(timer);
                    timer = setInterval(() => {
                        setWidth(Math.min(90, width + (90 - width) * 0.08));
                    }, 220);
                }
            }
            function done() {
                if (active > 0) return;
                clearInterval(timer);
                setWidth(100);
                setTimeout(() => {
                    bar.style.opacity = '0';
                    setTimeout(() => setWidth(0), 250);
                }, 180);
            }

            document.addEventListener('livewire:init', () => {
                Livewire.hook('request', ({ succeed, fail }) => {
                    active++;
                    start();
                    const finish = () => { active = Math.max(0, active - 1); done(); };
                    succeed(finish);
                    fail(finish);
                });

                document.addEventListener('livewire:navigate', () => { active++; start(); });
                document.addEventListener('livewire:navigated', () => { active = Math.max(0, active - 1); done(); });
            });
        })();
    </script>
</body>
</html>
