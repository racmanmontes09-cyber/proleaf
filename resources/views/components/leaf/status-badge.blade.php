@props([
    'type' => 'online', // online, offline, warning, info, standby
    'label' => null,
])

@php
    $classes = match($type) {
        'online', 'success', 'active', 'running' => 'bg-emerald-100 dark:bg-emerald-500/15 text-[#1B4332] dark:text-emerald-300 border-emerald-200 dark:border-emerald-500/30',
        'offline', 'error', 'critical' => 'bg-rose-100 dark:bg-rose-500/15 text-rose-800 dark:text-rose-300 border-rose-200 dark:border-rose-500/30',
        'warning' => 'bg-amber-100 dark:bg-amber-500/15 text-amber-900 dark:text-amber-300 border-amber-200 dark:border-amber-500/30',
        'standby', 'idle' => 'bg-slate-100 dark:bg-slate-500/15 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-500/30',
        'info' => 'bg-[#95D5B2]/30 dark:bg-leaf-300/15 text-[#1B4332] dark:text-leaf-200 border-[#2D6A4F]/20 dark:border-leaf-300/30',
        default => 'bg-slate-100 dark:bg-slate-500/15 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-500/30',
    };

    $dotColor = match($type) {
        'online', 'success', 'active', 'running' => 'bg-emerald-500',
        'offline', 'error', 'critical' => 'bg-rose-500',
        'warning' => 'bg-amber-500',
        'standby', 'idle' => 'bg-slate-400 dark:bg-slate-500',
        'info' => 'bg-[#2D6A4F] dark:bg-leaf-300',
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

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] lg:text-xs lg:leading-4 max-sm:text-[9px] max-sm:leading-3 max-sm:-translate-y-0.5 font-bold uppercase tracking-[0.18em] lg:tracking-normal max-sm:tracking-normal ' . $classes]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dotColor }} {{ in_array($type, ['online', 'active', 'running']) ? 'animate-pulse' : '' }}"></span>
    {{ $label ?? $defaultLabel }}
</span>
