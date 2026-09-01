@props([
    'title',
    'value',
    'unit' => '',
    'status' => 'Optimal',
    'statusType' => 'online',
    'target' => null,
    'trend' => null,
    'icon' => null,
    'sensorKey' => null,
])

<div {{ $attributes->merge(['class' => 'surface-card hover-lift group relative flex h-full max-sm:h-[72px] min-w-0 w-full flex-col justify-between overflow-hidden rounded-md max-sm:rounded-none p-5 max-sm:p-1 transition-all']) }}
    @if ($sensorKey)
        x-bind:class="kpiCardClass('{{ $sensorKey }}')"
    @endif
>
    <div class="mb-4 max-sm:mb-1 flex min-w-0 items-start justify-between gap-3 max-sm:gap-0.5">
        <div class="min-w-0 space-y-2 max-sm:space-y-0.5">
            <p class="truncate text-[11px] lg:text-sm lg:leading-5 max-sm:text-[10px] max-sm:leading-3 font-bold uppercase tracking-[0.18em] lg:tracking-normal max-sm:tracking-normal text-[#40916C] dark:text-leaf-300">{{ $title }}</p>
            <div class="flex min-w-0 items-baseline gap-1.5 max-sm:gap-0">
                <span class="truncate text-xl sm:text-2xl lg:text-3xl max-sm:text-base max-sm:leading-none font-extrabold tracking-normal text-[#1B4332] dark:text-slate-100 transition-all duration-300 ease-out"
                    @if ($sensorKey)
                        x-text="kpiValue('{{ $sensorKey }}')"
                        x-bind:class="kpiValueClass('{{ $sensorKey }}')"
                    @endif
                >{{ $value }}</span>
                @if ($unit)
                    <span class="shrink-0 text-xs lg:text-base lg:leading-5 max-sm:text-[10px] max-sm:leading-3 font-bold text-[#40916C] dark:text-leaf-300">{{ $unit }}</span>
                @endif
            </div>
        </div>

        @if ($icon)
            <div class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#2D6A4F]/10 dark:bg-white/10 text-[#2D6A4F] dark:text-leaf-300 transition-colors group-hover:bg-[#2D6A4F] group-hover:text-white sm:flex">
                {!! $icon !!}
            </div>
        @elseif ($sensorKey)
            <span class="inline-flex -translate-y-0.5 items-center max-sm:max-w-[3.75rem] max-sm:truncate text-[10px] lg:text-xs lg:leading-4 max-sm:text-[9px] max-sm:leading-3 font-bold uppercase tracking-[0.18em] lg:tracking-normal max-sm:tracking-normal transition-colors duration-300"
                x-bind:class="kpiStatusBadgeClass('{{ $sensorKey }}')"
            >
                <span x-text="kpiStatusLabel('{{ $sensorKey }}')">{{ $status }}</span>
            </span>
        @else
            <x-leaf.status-badge :type="$statusType" :label="$status" class="shrink-0" />
        @endif
    </div>

    <div class="mt-4 max-sm:mt-1 flex min-w-0 items-center justify-between gap-2 max-sm:gap-0.5 border-t border-[#2D6A4F]/10 dark:border-white/10 pt-3 max-sm:pt-1 text-[11px] lg:text-sm lg:leading-5 max-sm:text-[10px] max-sm:leading-3">
        @if ($icon && $sensorKey)
            <span class="inline-flex -translate-y-0.5 items-center max-sm:max-w-[3.75rem] max-sm:truncate text-[10px] lg:text-xs lg:leading-4 max-sm:text-[9px] max-sm:leading-3 font-bold uppercase tracking-[0.18em] lg:tracking-normal max-sm:tracking-normal transition-colors duration-300"
                x-bind:class="kpiStatusBadgeClass('{{ $sensorKey }}')"
            >
                <span x-text="kpiStatusLabel('{{ $sensorKey }}')">{{ $status }}</span>
            </span>
        @elseif ($icon)
            <x-leaf.status-badge :type="$statusType" :label="$status" class="shrink-0" />
        @else
            <span class="truncate font-semibold text-[#2D6A4F] dark:text-leaf-300 max-sm:text-[10px] max-sm:leading-3"
                @if ($sensorKey)
                    x-text="kpiTrend('{{ $sensorKey }}')"
                @endif
            >{{ $trend ?? '✓ Normal Range' }}</span>
        @endif

        @if ($target)
            <span class="min-w-0 truncate text-right font-mono text-[10px] lg:text-xs lg:leading-4 max-sm:text-[9px] max-sm:leading-3 text-gray-400 dark:text-slate-500">Target: {{ $target }}</span>
        @endif
    </div>
</div>
