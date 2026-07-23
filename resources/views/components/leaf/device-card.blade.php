@props([
    'name' => 'ESP32 Hydroponic Node',
    'deviceId' => 'LEAF-NODE-01',
    'isOnline' => true,
    'firmware' => 'v1.2.0-esp32',
    'wifiRssi' => '-58',
    'uptime' => '48:12:05',
    'freeHeap' => '218,400',
    'battery' => '95%',
    'mqttStatus' => 'CONNECTED',
    'lastSeen' => 'Just now',
])

<div {{ $attributes->merge(['class' => 'p-6 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-4 hover:shadow-md transition-all']) }}>
    
    <!-- Card Header -->
    <div class="flex items-center justify-between pb-3 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#2D6A4F]/10 text-[#2D6A4F] flex items-center justify-center font-bold text-base">
                📟
            </div>
            <div>
                <h3 class="text-base font-bold text-[#1B4332]">{{ $name }}</h3>
                <p class="text-xs font-mono text-[#2D6A4F]">{{ $deviceId }}</p>
            </div>
        </div>
        <x-leaf.status-badge :type="$isOnline ? 'online' : 'offline'" :label="$isOnline ? 'ONLINE' : 'OFFLINE'" />
    </div>

    <!-- Specs Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
        <div class="p-3 rounded-2xl bg-gray-50 border border-gray-100">
            <span class="text-gray-400 block text-[10px] uppercase tracking-wider font-semibold">Wi-Fi Signal</span>
            <span class="font-mono font-bold text-[#1B4332] text-sm">{{ $wifiRssi }} dBm</span>
        </div>
        <div class="p-3 rounded-2xl bg-gray-50 border border-gray-100">
            <span class="text-gray-400 block text-[10px] uppercase tracking-wider font-semibold">Battery</span>
            <span class="font-mono font-bold text-[#2D6A4F] text-sm">{{ $battery }}</span>
        </div>
        <div class="p-3 rounded-2xl bg-gray-50 border border-gray-100">
            <span class="text-gray-400 block text-[10px] uppercase tracking-wider font-semibold">MQTT Broker</span>
            <span class="font-mono font-bold text-emerald-700 text-sm">{{ $mqttStatus }}</span>
        </div>
        <div class="p-3 rounded-2xl bg-gray-50 border border-gray-100">
            <span class="text-gray-400 block text-[10px] uppercase tracking-wider font-semibold">Firmware</span>
            <span class="font-mono font-medium text-gray-700 text-xs">{{ $firmware }}</span>
        </div>
        <div class="p-3 rounded-2xl bg-gray-50 border border-gray-100">
            <span class="text-gray-400 block text-[10px] uppercase tracking-wider font-semibold">Free Heap</span>
            <span class="font-mono font-medium text-gray-700 text-xs">{{ $freeHeap }} B</span>
        </div>
        <div class="p-3 rounded-2xl bg-gray-50 border border-gray-100">
            <span class="text-gray-400 block text-[10px] uppercase tracking-wider font-semibold">Uptime</span>
            <span class="font-mono font-medium text-gray-700 text-xs">{{ $uptime }}</span>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="pt-2 flex items-center justify-between border-t border-gray-100 text-xs">
        <span class="text-gray-400 text-[11px]">Last Telemetry: <strong class="text-[#2D6A4F] font-semibold">{{ $lastSeen }}</strong></span>
        <div class="flex items-center gap-2">
            <button type="button" class="px-3 py-1.5 rounded-xl bg-gray-100 hover:bg-[#2D6A4F] hover:text-white text-[#1B4332] font-semibold transition-all">
                Restart Node
            </button>
            <button type="button" class="px-3 py-1.5 rounded-xl bg-[#2D6A4F]/10 hover:bg-[#2D6A4F] hover:text-white text-[#2D6A4F] font-semibold transition-all">
                Config
            </button>
        </div>
    </div>

</div>
