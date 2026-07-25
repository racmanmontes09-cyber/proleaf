<x-app-layout>
    <x-leaf.page-shell title="Device Management" description="Registered ESP32 devices and their current connection state.">
        @php
            $devices = App\Models\Device::query()->latest('last_seen_at')->get();
        @endphp

        @if ($devices->isEmpty())
            <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-8 text-center shadow-sm">
                <h2 class="text-lg font-semibold text-[#1B4332]">No devices registered yet</h2>
                <p class="mt-2 text-sm text-[#2D6A4F]/80">Device records will appear here once ESP32 hardware connects to the platform.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($devices as $device)
                    <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-6 shadow-sm">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-[#1B4332]">{{ $device->name ?? 'Waiting for device name...' }}</h2>
                                <p class="mt-1 text-sm text-[#2D6A4F]/80">Device ID: {{ $device->device_id ?? 'Waiting for device ID...' }}</p>
                            </div>
                            <div class="rounded-full bg-[#F8FAF8] px-3 py-1 text-sm font-semibold text-[#2D6A4F]">
                                {{ $device->is_online ? 'Online' : 'Offline' }}
                            </div>
                        </div>
                        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4 text-sm text-[#1B4332]">
                            <div class="rounded-xl bg-[#F8FAF8] px-3 py-2">Last Seen: {{ $device->last_seen_at ? $device->last_seen_at->format('Y-m-d H:i:s') : 'Waiting for device...' }}</div>
                            <div class="rounded-xl bg-[#F8FAF8] px-3 py-2">Firmware: {{ $device->firmware_version ?? 'Waiting for firmware...' }}</div>
                            <div class="rounded-xl bg-[#F8FAF8] px-3 py-2">Local IP: {{ $device->local_ip_address ?? 'Waiting for hardware...' }}</div>
                            <div class="rounded-xl bg-[#F8FAF8] px-3 py-2">WiFi RSSI: {{ $device->wifi_rssi !== null ? $device->wifi_rssi.' dBm' : 'Waiting for hardware...' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-leaf.page-shell>
</x-app-layout>
