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

<div {{ $attributes->merge(['class' => 'p-5 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm hover:shadow-md hover:border-[#2D6A4F]/30 transition-all group relative overflow-hidden flex flex-col justify-between w-full h-full min-w-0']) }}>
    
    <!-- Top Header Row -->
    <div class="flex items-center justify-between gap-2 text-xs font-semibold text-[#1B4332]/70 mb-2 min-w-0">
        <span class="truncate">{{ $title }}</span>
        @if ($icon)
            <div class="p-2 rounded-xl bg-[#2D6A4F]/10 text-[#2D6A4F] group-hover:bg-[#2D6A4F] group-hover:text-white transition-colors shrink-0">
                {!! $icon !!}
            </div>
        @else
            <x-leaf.status-badge :type="$statusType" :label="$status" class="shrink-0" />
        @endif
    </div>

    <!-- Value Display -->
    <div class="flex items-baseline gap-1.5 my-1 min-w-0">
        <span class="text-2xl font-extrabold text-[#1B4332] tracking-tight truncate">{{ $value }}</span>
        @if ($unit)
            <span class="text-xs font-bold text-[#40916C] shrink-0">{{ $unit }}</span>
        @endif
    </div>

    <!-- Footer Subtext & Target Range -->
    <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between gap-2 text-[11px] min-w-0">
        @if ($icon)
            <x-leaf.status-badge :type="$statusType" :label="$status" class="shrink-0" />
        @else
            <span class="text-[#2D6A4F] font-semibold truncate">{{ $trend ?? '✓ Normal Range' }}</span>
        @endif

        @if ($target)
            <span class="text-gray-400 font-mono text-[10px] truncate shrink-0">Target: {{ $target }}</span>
        @endif
    </div>

</div>

