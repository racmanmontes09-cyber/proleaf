@props([
    'title' => 'No Data Found',
    'description' => 'There are no items matching your criteria at this moment.',
    'icon' => '🍃',
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'page-frame px-6 py-10 text-center sm:px-8 sm:py-12']) }}>
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-[#2D6A4F]/10 text-3xl text-[#2D6A4F] shadow-inner">
        {{ $icon }}
    </div>
    <div class="mx-auto mt-5 max-w-lg space-y-2">
        <h3 class="text-lg font-extrabold text-[#1B4332] sm:text-xl">{{ $title }}</h3>
        <p class="text-sm leading-relaxed text-[#1B4332]/70">{{ $description }}</p>
    </div>
    @if ($action)
        <div class="mt-5 flex justify-center">
            {!! $action !!}
        </div>
    @endif
</div>
