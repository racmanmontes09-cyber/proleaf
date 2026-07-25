<x-app-layout>
    <x-leaf.page-shell title="Analytics" description="Historical charts, trends, and daily or weekly summaries.">
        @php
            $telemetries = App\Models\Telemetry::query()->latest('updated_at')->take(10)->get();
        @endphp

        @if ($telemetries->isEmpty())
            <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-8 text-center shadow-sm">
                <h2 class="text-lg font-semibold text-[#1B4332]">Waiting for historical data...</h2>
                <p class="mt-2 text-sm text-[#2D6A4F]/80">Historical charts will appear here once telemetry records have been collected.</p>
            </div>
        @else
            <div class="grid gap-4 lg:grid-cols-3">
                <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-[#1B4332]">Recent trend</h2>
                    <p class="mt-2 text-sm text-[#2D6A4F]/80">Latest telemetry samples are ready for trend review.</p>
                    <p class="mt-4 text-2xl font-bold text-[#2D6A4F]">{{ $telemetries->first()->updated_at?->diffForHumans() ?? 'Available' }}</p>
                </div>
                <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-[#1B4332]">Daily statistics</h2>
                    <p class="mt-2 text-sm text-[#2D6A4F]/80">Snapshot summaries will be surfaced here as telemetry grows.</p>
                    <p class="mt-4 text-2xl font-bold text-[#2D6A4F]">{{ $telemetries->count() }} samples</p>
                </div>
                <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-[#1B4332]">Weekly statistics</h2>
                    <p class="mt-2 text-sm text-[#2D6A4F]/80">Weekly comparisons will be added as more history is collected.</p>
                    <p class="mt-4 text-2xl font-bold text-[#2D6A4F]">{{ $telemetries->count() }} records</p>
                </div>
            </div>
        @endif
    </x-leaf.page-shell>
</x-app-layout>
