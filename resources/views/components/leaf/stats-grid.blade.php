@props([
    'cols' => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6',
])

<div {{ $attributes->merge(['class' => "grid {$cols} gap-4"]) }}>
    {{ $slot }}
</div>
