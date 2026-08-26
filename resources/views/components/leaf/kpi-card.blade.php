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
            <p class="truncate text-[11px] max-sm:text-[7px] font-bold uppercase tracking-[0.18em] max-sm:tracking-[0.04em] text-[#40916C]">{{ $title }}</p>
            <div class="flex items-baseline gap-1.5 max-sm:gap-0 min-w-0">
                <span class="truncate text-2xl max-sm:text-[7px] font-extrabold tracking-tight text-[#1B4332] transition-all duration-300 ease-out"
                    @if ($sensorKey)
                        x-text="kpiValue('{{ $sensorKey }}')"
                        x-bind:class="kpiValueClass('{{ $sensorKey }}')"
                    @endif
                >{{ $value }}</span>
                @if ($unit)
                    <span class="shrink-0 text-xs max-sm:text-[7px] font-bold text-[#40916C]">{{ $unit }}</span>
                @endif
            </div>
        </div>

        @if ($icon)
            <div class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#2D6A4F]/10 text-[#2D6A4F] transition-colors group-hover:bg-[#2D6A4F] group-hover:text-white sm:flex">
                {!! $icon !!}
            </div>
        @elseif ($sensorKey)
            <span class="inline-flex -translate-y-0.5 items-center text-[10px] max-sm:text-[6px] font-bold uppercase tracking-[0.18em] max-sm:tracking-[0.04em] transition-colors duration-300"
                x-bind:class="kpiStatusBadgeClass('{{ $sensorKey }}')"
            >
                <span x-text="kpiStatusLabel('{{ $sensorKey }}')">{{ $status }}</span>
            </span>
        @else
            <x-leaf.status-badge :type="$statusType" :label="$status" class="shrink-0" />
        @endif
    </div>

    <div class="mt-4 max-sm:mt-1 flex items-center justify-between gap-3 max-sm:gap-0.5 border-t border-[#2D6A4F]/10 pt-3 max-sm:pt-1 text-[11px] max-sm:text-[7px]">
        @if ($icon && $sensorKey)
            <span class="inline-flex -translate-y-0.5 items-center text-[10px] max-sm:text-[6px] font-bold uppercase tracking-[0.18em] max-sm:tracking-[0.04em] transition-colors duration-300"
                x-bind:class="kpiStatusBadgeClass('{{ $sensorKey }}')"
            >
                <span x-text="kpiStatusLabel('{{ $sensorKey }}')">{{ $status }}</span>
            </span>
        @elseif ($icon)
            <x-leaf.status-badge :type="$statusType" :label="$status" class="shrink-0" />
        @else
            <span class="truncate font-semibold text-[#2D6A4F] max-sm:text-[7px]"
                @if ($sensorKey)
                    x-text="kpiTrend('{{ $sensorKey }}')"
                @endif
            >{{ $trend ?? '✓ Normal Range' }}</span>
        @endif

        @if ($target)
            <span class="shrink-0 font-mono text-[10px] max-sm:text-[7px] text-gray-400">Target: {{ $target }}</span>
        @endif
    </div>
</div>
