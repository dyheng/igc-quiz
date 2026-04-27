@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'as' => 'button',
    'href' => null,
    'disabled' => false,
    'loadingTarget' => null,
])

@php
    $base = match($variant) {
        'secondary' => 'btn-secondary',
        'ghost' => 'btn-ghost',
        'danger' => 'btn-danger',
        default => 'btn-primary',
    };
    $sizeClass = $size === 'lg' ? 'btn-lg' : '';
    $classes = trim($base . ' ' . $sizeClass);

    $wireClick = $attributes->get('wire:click');
    $wireSubmit = $attributes->get('wire:submit');
    $resolvedTarget = $loadingTarget;
    if (! $resolvedTarget && $wireClick) {
        $resolvedTarget = trim(explode('(', $wireClick, 2)[0]);
    }
    if (! $resolvedTarget && $wireSubmit) {
        $resolvedTarget = trim(explode('(', $wireSubmit, 2)[0]);
    }
@endphp

@if ($as === 'a' || $href)
    <a href="{{ $disabled ? '#' : $href }}" @if($disabled) aria-disabled="true" tabindex="-1" @endif {{ $attributes->class([$classes, 'opacity-50 pointer-events-none' => $disabled]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}"
        @disabled($disabled)
        @if ($resolvedTarget)
            wire:loading.attr="disabled"
            wire:target="{{ $resolvedTarget }}"
        @endif
        {{ $attributes->class($classes) }}
    >
        @if ($resolvedTarget)
            <svg wire:loading.delay.shorter wire:target="{{ $resolvedTarget }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="animate-spin w-4 h-4 -ml-0.5">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
                <path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" class="opacity-75"/>
            </svg>
        @endif
        {{ $slot }}
    </button>
@endif
