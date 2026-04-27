@props(['padding' => true])

<div {{ $attributes->class(['card', 'p-5 md:p-6' => $padding]) }}>
    {{ $slot }}
</div>
