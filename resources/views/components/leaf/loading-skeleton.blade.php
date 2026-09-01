@props([
    'rows' => 3,
])

<div {{ $attributes->merge(['class' => 'space-y-4 animate-pulse']) }}>
    @for ($i = 0; $i < $rows; $i++)
        <div class="page-frame px-5 py-5 sm:px-6">
            <div class="h-3 w-1/4 rounded-full bg-gray-200 dark:bg-slate-700"></div>
            <div class="mt-3 h-8 w-1/2 rounded-2xl bg-gray-200 dark:bg-slate-700"></div>
            <div class="mt-4 h-3 w-full rounded-full bg-gray-100 dark:bg-slate-800"></div>
            <div class="mt-2 h-3 w-2/3 rounded-full bg-gray-100 dark:bg-slate-800"></div>
        </div>
    @endfor
</div>
