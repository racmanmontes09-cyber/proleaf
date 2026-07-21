<div wire:poll.5s class="max-w-3xl mx-auto p-6">

    <h1 class="text-3xl font-bold mb-6">
        Project L.E.A.F.
    </h1>

    @if ($device)

        <div class="bg-white rounded-lg shadow p-6">

            <h2 class="text-xl font-semibold mb-4">
                Device Status
            </h2>

            <div class="grid grid-cols-2 gap-4">

                <div>
                    <strong>Status</strong><br>

                    @if ($device->is_online)
                        <span class="text-green-600 font-bold">
                            🟢 Online
                        </span>
                    @else
                        <span class="text-red-600 font-bold">
                            🔴 Offline
                        </span>
                    @endif
                </div>

                <div>
                    <strong>Device ID</strong><br>
                    {{ $device->device_id }}
                </div>

                <div>
                    <strong>Name</strong><br>
                    {{ $device->name }}
                </div>

                <div>
                    <strong>Firmware</strong><br>
                    {{ $device->firmware_version }}
                </div>

                <div>
                    <strong>WiFi RSSI</strong><br>
                    {{ $device->wifi_rssi }} dBm
                </div>

                <div>
                    <strong>Uptime</strong><br>
                    {{ gmdate('H:i:s', $device->uptime_seconds) }}
                </div>

                <div>
                    <strong>Free Heap</strong><br>
                    {{ number_format($device->free_heap) }} bytes
                </div>

                <div>
                    <strong>Last Seen</strong><br>
                    {{ optional($device->last_seen_at)->diffForHumans() }}
                </div>

            </div>

        </div>

    @else

        <div class="bg-yellow-100 border border-yellow-300 rounded-lg p-6">
            No device connected.
        </div>

    @endif

</div>