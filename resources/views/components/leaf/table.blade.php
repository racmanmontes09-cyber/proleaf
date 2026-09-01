@props([
    'headers' => [],
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-3xl bg-white dark:bg-[#1E293B] border border-[#2D6A4F]/10 dark:border-white/10 shadow-sm max-w-full']) }}>
    <div class="overflow-x-auto max-w-full">
        <table class="min-w-[720px] w-full text-left text-sm sm:text-xs lg:text-sm lg:leading-6 max-sm:leading-5">
            @if (count($headers))
                <thead class="bg-[#F8FAF8] dark:bg-[#0F172A] border-b border-[#2D6A4F]/10 dark:border-white/10 text-[#1B4332] dark:text-slate-100 uppercase tracking-normal font-bold text-[11px] sm:text-[10px] lg:text-xs lg:leading-5 max-sm:leading-5">
                    <tr>
                        @foreach ($headers as $header)
                            <th scope="col" class="px-6 py-4">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody class="divide-y divide-gray-100 dark:divide-white/10 text-[#1B4332] dark:text-slate-300">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if (isset($footer))
        <div class="px-6 py-4 bg-[#F8FAF8] dark:bg-[#0F172A] border-t border-[#2D6A4F]/10 dark:border-white/10 text-sm sm:text-xs lg:text-sm lg:leading-6 max-sm:leading-5 flex items-center justify-between text-gray-500 dark:text-slate-400">
            {{ $footer }}
        </div>
    @endif
</div>
