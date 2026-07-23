@props([
    'rows' => 3,
])

<div {{ $attributes->merge(['class' => 'space-y-4 animate-pulse']) }}>
    @for ($i = 0; $i < $rows; $i++)
        <div class="p-6 rounded-3xl bg-white border border-[#2D6A4F]/10 space-y-3">
            <div class="h-4 bg-gray-200 rounded-full w-1/3"></div>
            <div class="h-8 bg-gray-200 rounded-2xl w-1/2"></div>
            <div class="h-3 bg-gray-100 rounded-full w-full"></div>
        </div>
    @endfor
</div>
