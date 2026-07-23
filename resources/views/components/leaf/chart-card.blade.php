@props([
    'chartId',
    'title',
    'subtitle' => null,
    'badge' => 'Live Updates',
    'options' => [],
])

<div {{ $attributes->merge(['class' => 'p-6 sm:p-8 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-4']) }}>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100">
        <div>
            <div class="flex items-center gap-2">
                <h3 class="text-lg font-bold text-[#1B4332]">{{ $title }}</h3>
                @if ($badge)
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#95D5B2]/30 text-[#1B4332] border border-[#2D6A4F]/10">
                        {{ $badge }}
                    </span>
                @endif
            </div>
            @if ($subtitle)
                <p class="text-xs text-[#1B4332]/70 mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>

        <!-- Filter Period Pill -->
        <div class="flex items-center gap-1 bg-[#F8FAF8] p-1 rounded-xl border border-[#2D6A4F]/10 text-xs font-semibold text-[#1B4332]/70">
            <button type="button" class="px-3 py-1 rounded-lg bg-[#2D6A4F] text-white shadow-sm transition-all">24h</button>
            <button type="button" class="px-3 py-1 rounded-lg hover:bg-[#2D6A4F]/10 transition-all">7d</button>
            <button type="button" class="px-3 py-1 rounded-lg hover:bg-[#2D6A4F]/10 transition-all">30d</button>
        </div>
    </div>

    <!-- Chart Mount Container -->
    <div id="{{ $chartId }}" class="w-full h-72"></div>
</div>
