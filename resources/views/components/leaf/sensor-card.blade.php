@props([
    'name',
    'value',
    'unit' => '',
    'status' => 'Waiting',
    'statusType' => 'standby',
    'min' => '0',
    'max' => '100',
    'percentage' => 0,
    'optimalRange' => 'Not available',
    'lastCalibrated' => 'Waiting for sensor data...',
    'icon' => null,
])

@php
    $minLabel = is_numeric($min) ? $min.$unit : $min;
    $maxLabel = is_numeric($max) ? $max.$unit : $max;
    $percentageValue = is_numeric($percentage) ? $percentage : 0;
@endphp

<div {{ $attributes->merge(['class' => 'p-6 rounded-3xl bg-white dark:bg-[#1E293B] border border-[#2D6A4F]/10 dark:border-white/10 shadow-sm hover:shadow-md hover:border-[#2D6A4F]/30 dark:hover:border-leaf-300/40 transition-all group flex flex-col justify-between w-full h-full min-w-0']) }}>
    <div class="flex items-center justify-between gap-3 mb-4 min-w-0">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-2xl bg-[#2D6A4F]/10 dark:bg-white/10 text-[#2D6A4F] dark:text-leaf-300 flex items-center justify-center shrink-0 group-hover:bg-[#2D6A4F] group-hover:text-white transition-colors">
                @if ($icon)
                    {!! $icon !!}
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                @endif
            </div>
            <div class="min-w-0">
                <h4 class="text-base sm:text-sm lg:text-base font-bold text-[#1B4332] dark:text-slate-100 truncate">{{ $name }}</h4>
                <p class="text-xs sm:text-[11px] lg:text-xs lg:leading-4 text-[#40916C] dark:text-leaf-300 truncate">Updated: {{ $lastCalibrated }}</p>
            </div>
        </div>

        <x-leaf.status-badge :type="$statusType" :label="$status" class="shrink-0" />
    </div>

    <div class="flex items-baseline justify-between gap-2 my-2 min-w-0">
        <div class="flex items-baseline gap-1 min-w-0">
            <span class="text-3xl font-extrabold text-[#1B4332] dark:text-slate-100 tracking-normal truncate">{{ $value }}</span>
            <span class="text-sm font-bold text-[#40916C] dark:text-leaf-300 shrink-0">{{ $unit }}</span>
        </div>
        <span class="text-sm sm:text-xs lg:text-sm lg:leading-5 font-medium text-gray-500 dark:text-slate-400 font-mono shrink-0">Range: {{ $optimalRange }}</span>
    </div>

    <!-- Gauge Progress Bar -->
    <div class="mt-4 space-y-1.5 min-w-0">
        <div class="w-full bg-gray-100 dark:bg-slate-700/60 rounded-full h-2 overflow-hidden">
            <div class="bg-gradient-to-r from-[#40916C] to-[#2D6A4F] h-2 rounded-full transition-all duration-500" style="width: {{ $percentageValue }}%"></div>
        </div>
        <div class="flex justify-between text-xs sm:text-[10px] lg:text-xs lg:leading-4 text-gray-400 dark:text-slate-500 font-mono">
            <span>Min: {{ $minLabel }}</span>
            <span>Max: {{ $maxLabel }}</span>
        </div>
    </div>
</div>
