<x-app-layout>
    <x-leaf.page-shell title="Alerts & Logs" description="Telemetry alerts, event history, and system activity.">
        @php
            $alerts = App\Models\Alert::query()
                ->latest('created_at')
                ->limit(20)
                ->get();
        @endphp

        @if ($alerts->isEmpty())
            <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 dark:border-white/10 p-8 text-center shadow-sm">
                <h2 class="text-lg lg:text-xl lg:leading-7 font-semibold text-[#1B4332] dark:text-slate-100">No alerts available.</h2>
                <p class="mt-2 text-sm lg:text-base lg:leading-6 text-[#2D6A4F]/80 dark:text-leaf-300/80">Telemetry alerts and event history will appear here as the system receives new data.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($alerts as $alert)
                    <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 dark:border-white/10 p-5 shadow-sm">
                        <div class="flex flex-col gap-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="h-2.5 w-2.5 rounded-full bg-[#2D6A4F]"></span>
                                    <p class="text-sm lg:text-base lg:leading-6 font-semibold text-[#1B4332] dark:text-slate-100">{{ $alert->title }}</p>
                                </div>
                                <span class="text-xs lg:text-sm lg:leading-5 uppercase tracking-normal text-[#2D6A4F] dark:text-leaf-300">{{ ucfirst($alert->status) }}</span>
                            </div>
                            <p class="text-sm lg:text-base lg:leading-6 text-[#2D6A4F]/90 dark:text-leaf-300/90">{{ $alert->message }}</p>
                            <div class="flex items-center justify-between text-xs lg:text-sm lg:leading-5 text-[#95D5B2]/90 dark:text-leaf-200/90">
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
