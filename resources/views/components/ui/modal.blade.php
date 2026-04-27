@props([
    'name',
    'title' => null,
    'maxWidth' => 'max-w-md',
])

<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title-{{ $name }}"
    role="dialog"
    aria-modal="true"
>
    <div class="flex min-h-full items-end sm:items-center justify-center p-4">
        <div
            x-show="open"
            x-transition.opacity
            class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
            x-on:click="open = false"
        ></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 translate-y-2 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="relative card p-5 md:p-6 w-full {{ $maxWidth }}"
        >
            @if ($title)
                <h2 id="modal-title-{{ $name }}" class="text-lg font-semibold text-slate-900 mb-3">{{ $title }}</h2>
            @endif
            <div>{{ $slot }}</div>
        </div>
    </div>
</div>
