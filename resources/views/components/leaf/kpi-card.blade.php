@props([
    'title',
    'value',
    'unit' => '',
    'status' => 'Optimal',
    'statusType' => 'online',
    'target' => null,
    'trend' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'surface-card hover-lift group relative flex h-full min-w-0 w-full flex-col justify-between overflow-hidden p-5 transition-all']) }}>
    <div class="mb-4 flex min-w-0 items-start justify-between gap-3">
        <div class="min-w-0 space-y-2">
            <p class="truncate text-[11px] font-bold uppercase tracking-[0.18em] text-[#40916C]">{{ $title }}</p>
            <div class="flex items-baseline gap-1.5 min-w-0">
                <span class="truncate text-2xl font-extrabold tracking-tight text-[#1B4332]">{{ $value }}</span>
                @if ($unit)
                    <span class="shrink-0 text-xs font-bold text-[#40916C]">{{ $unit }}</span>
                @endif
            </div>
        </div>

        @if ($icon)
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#2D6A4F]/10 text-[#2D6A4F] transition-colors group-hover:bg-[#2D6A4F] group-hover:text-white">
                {!! $icon !!}
            </div>
        @else
            <x-leaf.status-badge :type="$statusType" :label="$status" class="shrink-0" />
        @endif
    </div>

    <div class="mt-4 flex items-center justify-between gap-3 border-t border-[#2D6A4F]/10 pt-3 text-[11px]">
        @if ($icon)
            <x-leaf.status-badge :type="$statusType" :label="$status" class="shrink-0" />
        @else
            <span class="truncate font-semibold text-[#2D6A4F]">{{ $trend ?? '✓ Normal Range' }}</span>
        @endif

        @if ($target)
            <span class="shrink-0 font-mono text-[10px] text-gray-400">Target: {{ $target }}</span>
        @endif
    </div>
</div>

