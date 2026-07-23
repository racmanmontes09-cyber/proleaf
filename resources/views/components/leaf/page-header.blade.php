@props([
    'title',
    'subtitle' => null,
    'badge' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->merge(['class' => 'flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-[#2D6A4F]/10']) }}>
    <div class="space-y-1">
        @if (count($breadcrumbs))
            <nav class="flex items-center gap-2 text-xs font-semibold text-[#40916C] mb-1">
                @foreach ($breadcrumbs as $crumb => $link)
                    @if ($loop->last)
                        <span class="text-[#1B4332] font-bold">{{ $crumb }}</span>
                    @else
                        <a href="{{ $link }}" class="hover:text-[#2D6A4F] transition-colors">{{ $crumb }}</a>
                        <span>/</span>
                    @endif
                @endforeach
            </nav>
        @endif

        <div class="flex items-center gap-3">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#1B4332] tracking-tight">
                {{ $title }}
            </h1>
            @if ($badge)
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#2D6A4F]/10 text-[#2D6A4F] border border-[#2D6A4F]/20">
                    {{ $badge }}
                </span>
            @endif
        </div>

        @if ($subtitle)
            <p class="text-xs sm:text-sm text-[#1B4332]/70 font-normal">
                {{ $subtitle }}
            </p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex items-center gap-3">
            {{ $actions }}
        </div>
    @endif
</div>
