@props(['title', 'description' => null, 'actions' => null])

<div class="space-y-6">
    <div class="page-frame px-4 py-5 sm:px-6 sm:py-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0 space-y-2">
                <p class="section-kicker">Project L.E.A.F.</p>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-extrabold tracking-normal leading-tight lg:text-4xl text-[#1B4332] dark:text-slate-100 sm:text-3xl">{{ $title }}</h1>
                </div>
                @if ($description)
                    <p class="section-description max-w-3xl">{{ $description }}</p>
                @endif
            </div>

            @if ($actions)
                <div class="shrink-0 flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endif
        </div>
    </div>

    <div class="space-y-4">
        {{ $slot }}
    </div>
</div>
