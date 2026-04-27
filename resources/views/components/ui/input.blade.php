@props([
    'label' => null,
    'id' => null,
    'name' => null,
    'type' => 'text',
    'error' => null,
    'hint' => null,
])

@php
    $resolvedId = $id ?? $name ?? ('input-' . uniqid());
@endphp

<div class="space-y-1.5">
    @if ($label)
        <label for="{{ $resolvedId }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    @endif
    <input
        id="{{ $resolvedId }}"
        @if ($name) name="{{ $name }}" @endif
        type="{{ $type }}"
        {{ $attributes->class([
            'block w-full',
            'border-rose-300 focus:border-rose-500 focus:ring-rose-500' => $error,
        ]) }}
    />
    @if ($error)
        <p class="text-xs text-rose-600">{{ $error }}</p>
    @elseif ($hint)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
