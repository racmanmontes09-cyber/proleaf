@props([
    'type' => 'online', // online, offline, warning, info, standby
    'label' => null,
])

@php
    $classes = match($type) {
        'online', 'success', 'active', 'running' => 'bg-emerald-100 text-[#1B4332] border-emerald-200',
        'offline', 'error', 'critical' => 'bg-rose-100 text-rose-800 border-rose-200',
        'warning' => 'bg-amber-100 text-amber-900 border-amber-200',
        'standby', 'idle' => 'bg-gray-100 text-gray-700 border-gray-200',
        'info' => 'bg-[#95D5B2]/30 text-[#1B4332] border-[#2D6A4F]/20',
        default => 'bg-gray-100 text-gray-700 border-gray-200',
    };

    $dotColor = match($type) {
        'online', 'success', 'active', 'running' => 'bg-emerald-500',
        'offline', 'error', 'critical' => 'bg-rose-500',
        'warning' => 'bg-amber-500',
        'standby', 'idle' => 'bg-gray-400',
        'info' => 'bg-[#2D6A4F]',
        default => 'bg-gray-400',
    };

    $defaultLabel = match($type) {
        'online' => 'ONLINE',
        'offline' => 'OFFLINE',
        'active' => 'ACTIVE',
        'running' => 'RUNNING',
        'warning' => 'WARNING',
        'standby' => 'STANDBY',
        'idle' => 'IDLE',
        default => strtoupper($type),
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border tracking-wider uppercase ' . $classes]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }} {{ in_array($type, ['online', 'active', 'running']) ? 'animate-pulse' : '' }}"></span>
    {{ $label ?? $defaultLabel }}
</span>
