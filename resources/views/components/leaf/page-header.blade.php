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
                <nav class="flex items-center gap-2 text-xs sm:text-[11px] lg:text-[13px] lg:leading-5 font-semibold uppercase tracking-[0.12em] lg:tracking-normal text-[#40916C] dark:text-leaf-300">
                    @foreach ($breadcrumbs as $crumb => $link)
                        @if ($loop->last)
                            <span class="text-[#1B4332] dark:text-slate-100 font-bold">{{ $crumb }}</span>
                        @else
                            <a href="{{ $link }}" class="hover:text-[#2D6A4F] dark:hover:text-leaf-200 transition-colors">{{ $crumb }}</a>
                            <span>/</span>
                        @endif
                    @endforeach
                </nav>
            @endif

            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-[#1B4332] dark:text-slate-100 tracking-normal leading-tight">
                    {{ $title }}
                </h1>
                @if ($badge)
                    <span class="inline-flex items-center rounded-full border border-[#2D6A4F]/15 dark:border-leaf-300/30 bg-[#2D6A4F]/10 dark:bg-leaf-300/15 px-3 py-1 text-xs sm:text-[11px] lg:text-[13px] lg:leading-5 font-bold uppercase tracking-[0.12em] lg:tracking-normal text-[#2D6A4F] dark:text-leaf-300">
                        {{ $badge }}
                    </span>
                @endif
            </div>

            @if ($subtitle)
                <p class="text-sm leading-5 lg:text-base lg:leading-6 text-[#1B4332]/70 dark:text-slate-400">
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
