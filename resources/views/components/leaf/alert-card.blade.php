@props([
    'title',
    'message',
    'time' => 'Just now',
    'severity' => 'info', // info, warning, danger, success
    'read' => false,
])

@php
    $styles = [
        'info' => [
            'border' => 'border-[#2D6A4F]/20 dark:border-leaf-300/30',
            'bg' => 'bg-emerald-50/50 dark:bg-leaf-300/10',
            'badge' => 'bg-[#95D5B2]/30 text-[#1B4332] dark:bg-leaf-300/20 dark:text-leaf-200',
            'iconBg' => 'bg-[#2D6A4F]/10 text-[#2D6A4F] dark:bg-white/10 dark:text-leaf-300',
            'dot' => 'bg-[#2D6A4F] dark:bg-leaf-300',
        ],
        'warning' => [
            'border' => 'border-amber-200 dark:border-amber-500/30',
            'bg' => 'bg-amber-50/50 dark:bg-amber-500/10',
            'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300',
            'iconBg' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
            'dot' => 'bg-amber-500',
        ],
        'danger' => [
            'border' => 'border-rose-200 dark:border-rose-500/30',
            'bg' => 'bg-rose-50/50 dark:bg-rose-500/10',
            'badge' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300',
            'iconBg' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300',
            'dot' => 'bg-rose-500',
        ],
        'success' => [
            'border' => 'border-emerald-200 dark:border-emerald-500/30',
            'bg' => 'bg-emerald-50/50 dark:bg-emerald-500/10',
            'badge' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300',
            'iconBg' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
            'dot' => 'bg-emerald-500',
        ],
    ][$severity] ?? [
        'border' => 'border-gray-200 dark:border-white/10',
        'bg' => 'bg-white dark:bg-slate-800',
        'badge' => 'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-slate-200',
        'iconBg' => 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-300',
        'dot' => 'bg-gray-500',
    ];
@endphp

<div {{ $attributes->merge(['class' => "p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#1E293B] border {$styles['border']} shadow-sm flex items-start gap-4 transition-all hover:shadow-md"]) }}>
    <div class="w-10 h-10 rounded-xl {{ $styles['iconBg'] }} flex items-center justify-center shrink-0 mt-0.5">
        @if ($severity === 'warning')
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        @elseif ($severity === 'danger')
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        @elseif ($severity === 'success')
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        @else
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        @endif
    </div>

    <div class="flex-1 space-y-1">
        <div class="flex items-center justify-between">
            <h4 class="text-sm lg:text-base lg:leading-6 font-bold text-[#1B4332] dark:text-slate-100 flex items-center gap-2">
                {{ $title }}
                @if (!$read)
                    <span class="w-2 h-2 rounded-full {{ $styles['dot'] }} animate-pulse"></span>
                @endif
            </h4>
            <span class="text-[11px] lg:text-xs lg:leading-4 font-mono text-gray-400 dark:text-slate-500">{{ $time }}</span>
        </div>
        <p class="text-xs lg:text-sm lg:leading-6 text-gray-600 dark:text-slate-400 leading-relaxed">{{ $message }}</p>
    </div>
</div>
