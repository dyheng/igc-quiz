@props(['variant' => 'slate'])

@php
    $class = match ($variant) {
        'brand' => 'badge-brand',
        'success' => 'badge-success',
        'danger' => 'badge-danger',
        'warning' => 'badge-warning',
        default => 'badge-slate',
    };
@endphp

<span {{ $attributes->class($class) }}>{{ $slot }}</span>
