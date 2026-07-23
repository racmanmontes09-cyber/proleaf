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
            'border' => 'border-[#2D6A4F]/20',
            'bg' => 'bg-emerald-50/50',
            'badge' => 'bg-[#95D5B2]/30 text-[#1B4332]',
            'iconBg' => 'bg-[#2D6A4F]/10 text-[#2D6A4F]',
            'dot' => 'bg-[#2D6A4F]',
        ],
        'warning' => [
            'border' => 'border-amber-200',
            'bg' => 'bg-amber-50/50',
            'badge' => 'bg-amber-100 text-amber-800',
            'iconBg' => 'bg-amber-100 text-amber-700',
            'dot' => 'bg-amber-500',
        ],
        'danger' => [
            'border' => 'border-rose-200',
            'bg' => 'bg-rose-50/50',
            'badge' => 'bg-rose-100 text-rose-800',
            'iconBg' => 'bg-rose-100 text-rose-700',
            'dot' => 'bg-rose-500',
        ],
        'success' => [
            'border' => 'border-emerald-200',
            'bg' => 'bg-emerald-50/50',
            'badge' => 'bg-emerald-100 text-emerald-800',
            'iconBg' => 'bg-emerald-100 text-emerald-700',
            'dot' => 'bg-emerald-500',
        ],
    ][$severity] ?? [
        'border' => 'border-gray-200',
        'bg' => 'bg-white',
        'badge' => 'bg-gray-100 text-gray-800',
        'iconBg' => 'bg-gray-100 text-gray-700',
        'dot' => 'bg-gray-500',
    ];
@endphp

<div {{ $attributes->merge(['class' => "p-4 sm:p-5 rounded-2xl bg-white border {$styles['border']} shadow-sm flex items-start gap-4 transition-all hover:shadow-md"]) }}>
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
            <h4 class="text-sm font-bold text-[#1B4332] flex items-center gap-2">
                {{ $title }}
                @if (!$read)
                    <span class="w-2 h-2 rounded-full {{ $styles['dot'] }} animate-pulse"></span>
                @endif
            </h4>
            <span class="text-[11px] font-mono text-gray-400">{{ $time }}</span>
        </div>
        <p class="text-xs text-gray-600 leading-relaxed">{{ $message }}</p>
    </div>
</div>
