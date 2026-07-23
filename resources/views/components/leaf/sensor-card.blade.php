@props([
    'name',
    'value',
    'unit' => '',
    'status' => 'Optimal',
    'statusType' => 'online',
    'min' => '0',
    'max' => '100',
    'percentage' => 75,
    'optimalRange' => '20-25°C',
    'lastCalibrated' => '2 days ago',
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'p-6 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm hover:shadow-md hover:border-[#2D6A4F]/30 transition-all group flex flex-col justify-between w-full h-full min-w-0']) }}>
    <div class="flex items-center justify-between gap-3 mb-4 min-w-0">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-10 h-10 rounded-2xl bg-[#2D6A4F]/10 text-[#2D6A4F] flex items-center justify-center shrink-0 group-hover:bg-[#2D6A4F] group-hover:text-white transition-colors">
                @if ($icon)
                    {!! $icon !!}
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                @endif
            </div>
            <div class="min-w-0">
                <h4 class="text-sm font-bold text-[#1B4332] truncate">{{ $name }}</h4>
                <p class="text-[11px] text-[#40916C] truncate">Calibrated: {{ $lastCalibrated }}</p>
            </div>
        </div>

        <x-leaf.status-badge :type="$statusType" :label="$status" class="shrink-0" />
    </div>

    <div class="flex items-baseline justify-between gap-2 my-2 min-w-0">
        <div class="flex items-baseline gap-1 min-w-0">
            <span class="text-3xl font-extrabold text-[#1B4332] tracking-tight truncate">{{ $value }}</span>
            <span class="text-sm font-bold text-[#40916C] shrink-0">{{ $unit }}</span>
        </div>
        <span class="text-xs font-medium text-gray-500 font-mono shrink-0">Range: {{ $optimalRange }}</span>
    </div>

    <!-- Gauge Progress Bar -->
    <div class="mt-4 space-y-1.5 min-w-0">
        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
            <div class="bg-gradient-to-r from-[#40916C] to-[#2D6A4F] h-2 rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
        </div>
        <div class="flex justify-between text-[10px] text-gray-400 font-mono">
            <span>Min: {{ $min }}{{ $unit }}</span>
            <span>Max: {{ $max }}{{ $unit }}</span>
        </div>
    </div>
</div>
