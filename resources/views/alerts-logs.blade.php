<x-app-layout>
    <x-leaf.page-shell title="Alerts & Logs" description="Telemetry alerts, event history, and system activity.">
        @php
            $alerts = App\Models\Alert::query()
                ->latest('created_at')
                ->limit(20)
                ->get();
        @endphp

        @if ($alerts->isEmpty())
            <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-8 text-center shadow-sm">
                <h2 class="text-lg font-semibold text-[#1B4332]">No alerts available.</h2>
                <p class="mt-2 text-sm text-[#2D6A4F]/80">Telemetry alerts and event history will appear here as the system receives new data.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($alerts as $alert)
                    <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-5 shadow-sm">
                        <div class="flex flex-col gap-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="h-2.5 w-2.5 rounded-full bg-[#2D6A4F]"></span>
                                    <p class="text-sm font-semibold text-[#1B4332]">{{ $alert->title }}</p>
                                </div>
                                <span class="text-xs uppercase tracking-wider text-[#2D6A4F]">{{ ucfirst($alert->status) }}</span>
                            </div>
                            <p class="text-sm text-[#2D6A4F]/90">{{ $alert->message }}</p>
                            <div class="flex items-center justify-between text-xs text-[#95D5B2]/90">
                                <span>{{ $alert->sensor }}</span>
                                <span>{{ $alert->created_at?->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-leaf.page-shell>
</x-app-layout>
