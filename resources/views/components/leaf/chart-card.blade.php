@props([
    'chartId',
    'title',
    'subtitle' => null,
    'badge' => 'Live Updates',
    'options' => [],
])

<div {{ $attributes->merge(['class' => 'p-6 sm:p-8 rounded-3xl bg-white dark:bg-[#1E293B] border border-[#2D6A4F]/10 dark:border-white/10 shadow-sm space-y-4']) }}>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100 dark:border-white/10">
        <div>
            <div class="flex items-center gap-2">
                <h3 class="text-lg lg:text-xl font-bold text-[#1B4332] dark:text-slate-100">{{ $title }}</h3>
                @if ($badge)
                    <span class="px-2.5 py-0.5 rounded-full text-xs sm:text-[10px] lg:text-xs lg:leading-4 font-bold bg-[#95D5B2]/30 dark:bg-leaf-300/15 text-[#1B4332] dark:text-leaf-200 border border-[#2D6A4F]/10 dark:border-leaf-300/25">
                        {{ $badge }}
                    </span>
                @endif
            </div>
            @if ($subtitle)
                <p class="text-sm sm:text-xs lg:text-sm text-[#1B4332]/70 dark:text-slate-400 mt-0.5 leading-5 sm:leading-normal lg:leading-6">{{ $subtitle }}</p>
            @endif
        </div>

        <!-- Filter Period Pill -->
        <div class="flex items-center gap-1 bg-[#F8FAF8] dark:bg-[#0F172A] p-1 rounded-xl border border-[#2D6A4F]/10 dark:border-white/10 text-sm sm:text-xs lg:text-sm lg:leading-5 font-semibold text-[#1B4332]/70 dark:text-slate-300">
            <button type="button" class="px-3 py-1 rounded-lg bg-[#2D6A4F] dark:bg-leaf-300 text-white dark:text-[#081C15] shadow-sm transition-all">24h</button>
            <button type="button" class="px-3 py-1 rounded-lg hover:bg-[#2D6A4F]/10 dark:hover:bg-white/10 transition-all">7d</button>
            <button type="button" class="px-3 py-1 rounded-lg hover:bg-[#2D6A4F]/10 dark:hover:bg-white/10 transition-all">30d</button>
        </div>
    </div>

    <!-- Chart Mount Container -->
    <div id="{{ $chartId }}" class="w-full h-72"></div>
</div>
