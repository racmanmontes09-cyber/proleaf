@props(['title', 'description' => null, 'actions' => null])

<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight text-[#1B4332]">{{ $title }}</h1>
            @if ($description)
                <p class="mt-1 text-sm text-[#2D6A4F]/80">{{ $description }}</p>
            @endif
        </div>

        @if ($actions)
            <div class="shrink-0">{{ $actions }}</div>
        @endif
    </div>

    {{ $slot }}
</div>
