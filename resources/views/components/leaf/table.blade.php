@props([
    'headers' => [],
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm max-w-full']) }}>
    <div class="overflow-x-auto max-w-full">
        <table class="min-w-[720px] w-full text-left text-xs">
            @if (count($headers))
                <thead class="bg-[#F8FAF8] border-b border-[#2D6A4F]/10 text-[#1B4332] uppercase tracking-wider font-bold text-[10px]">
                    <tr>
                        @foreach ($headers as $header)
                            <th scope="col" class="px-6 py-4">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody class="divide-y divide-gray-100 text-[#1B4332]">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if (isset($footer))
        <div class="px-6 py-4 bg-[#F8FAF8] border-t border-[#2D6A4F]/10 text-xs flex items-center justify-between text-gray-500">
            {{ $footer }}
        </div>
    @endif
</div>
