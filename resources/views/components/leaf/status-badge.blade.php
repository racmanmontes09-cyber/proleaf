@props([
    'type' => 'online', // online, offline, warning, info, standby
    'label' => null,
])

@php
    $classes = match($type) {
        'online', 'success', 'active', 'running' => 'bg-emerald-100 text-[#1B4332] border-emerald-200',
        'offline', 'error', 'critical' => 'bg-rose-100 text-rose-800 border-rose-200',
        'warning' => 'bg-amber-100 text-amber-900 border-amber-200',
        'standby', 'idle' => 'bg-slate-100 text-slate-700 border-slate-200',
        'info' => 'bg-[#95D5B2]/30 text-[#1B4332] border-[#2D6A4F]/20',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };

    $dotColor = match($type) {
        'online', 'success', 'active', 'running' => 'bg-emerald-500',
        'offline', 'error', 'critical' => 'bg-rose-500',
        'warning' => 'bg-amber-500',
        'standby', 'idle' => 'bg-slate-400',
        'info' => 'bg-[#2D6A4F]',
        default => 'bg-slate-400',
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

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] max-sm:text-[7px] max-sm:-translate-y-0.5 font-bold uppercase tracking-[0.18em] max-sm:tracking-[0.04em] ' . $classes]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dotColor }} {{ in_array($type, ['online', 'active', 'running']) ? 'animate-pulse' : '' }}"></span>
    {{ $label ?? $defaultLabel }}
</span>
