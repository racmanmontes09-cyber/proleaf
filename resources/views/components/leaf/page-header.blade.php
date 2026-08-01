@props([
    'title',
    'subtitle' => null,
    'badge' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->merge(['class' => 'page-frame px-4 py-5 sm:px-6 sm:py-6']) }}>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="space-y-2 min-w-0">
            @if (count($breadcrumbs))
                <nav class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#40916C]">
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

            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#1B4332] tracking-tight">
                    {{ $title }}
                </h1>
                @if ($badge)
                    <span class="inline-flex items-center rounded-full border border-[#2D6A4F]/15 bg-[#2D6A4F]/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-[#2D6A4F]">
                        {{ $badge }}
                    </span>
                @endif
            </div>

            @if ($subtitle)
                <p class="text-sm text-[#1B4332]/70">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @if (isset($actions))
            <div class="flex items-center gap-2 flex-wrap">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
