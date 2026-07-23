@props([
    'title' => 'No Data Found',
    'description' => 'There are no items matching your criteria at this moment.',
    'icon' => '🍃',
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'p-8 sm:p-12 rounded-3xl bg-white border border-[#2D6A4F]/10 text-center space-y-4 shadow-sm']) }}>
    <div class="w-16 h-16 rounded-3xl bg-[#2D6A4F]/10 text-[#2D6A4F] flex items-center justify-center mx-auto text-3xl shadow-inner">
        {{ $icon }}
    </div>
    <div class="space-y-1 max-w-md mx-auto">
        <h3 class="text-lg font-extrabold text-[#1B4332]">{{ $title }}</h3>
        <p class="text-xs sm:text-sm text-gray-500 leading-relaxed">{{ $description }}</p>
    </div>
    @if ($action)
        <div class="pt-2">
            {!! $action !!}
        </div>
    @endif
</div>
