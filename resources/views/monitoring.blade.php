<x-app-layout>
    <x-leaf.page-shell title="Monitoring" description="Real-time monitoring of telemetry and device health.">
        @php
            $device = App\Models\Device::query()->latest('last_seen_at')->first();
            $telemetry = $device?->telemetries()->latest('updated_at')->first();
        @endphp

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-[#1B4332]">Live telemetry overview</h2>
                <p class="mt-2 text-sm text-[#2D6A4F]/80">Telemetry data is available through the existing dashboard workflow and remains connected to the live device records.</p>
                <div class="mt-4 space-y-3 text-sm text-[#1B4332]">
                    <div class="flex items-center justify-between rounded-xl bg-[#F8FAF8] px-3 py-2">
                        <span>Device</span>
                        <span class="font-semibold">{{ $device?->name ?? 'Waiting for device...' }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-[#F8FAF8] px-3 py-2">
                        <span>Status</span>
                        <span class="font-semibold">{{ $device ? ($device->is_online ? 'Online' : 'Offline') : 'Waiting for device...' }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-[#F8FAF8] px-3 py-2">
                        <span>Temperature</span>
                        <span class="font-semibold">{{ $telemetry && $telemetry->air_temperature !== null ? number_format((float) $telemetry->air_temperature, 1).' °C' : 'Waiting for telemetry...' }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-[#F8FAF8] px-3 py-2">
                        <span>Humidity</span>
                        <span class="font-semibold">{{ $telemetry && $telemetry->humidity !== null ? number_format((float) $telemetry->humidity, 0).' %' : 'Waiting for telemetry...' }}</span>
                    </div>
                </div>
            </div>

            <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-[#1B4332]">Monitoring status</h2>
                <p class="mt-2 text-sm text-[#2D6A4F]/80">The navigation entry is now active and points to a real page that uses the existing telemetry model.</p>
                <div class="mt-4 rounded-2xl border border-[#2D6A4F]/10 bg-[#F8FAF8] p-4 text-sm text-[#1B4332]">
                    <p class="font-semibold">Current connection</p>
                    <p class="mt-2 text-[#2D6A4F]/80">{{ $device ? 'The latest device record is available for live monitoring.' : 'Waiting for a registered device record.' }}</p>
                </div>
            </div>
        </div>
    </x-leaf.page-shell>
</x-app-layout>
