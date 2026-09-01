@props([
    'name' => 'Waiting for device...',
    'deviceId' => 'Waiting for device ID...',
    'isOnline' => false,
    'statusLabel' => 'Waiting',
    'statusType' => 'standby',
    'firmware' => 'Waiting for firmware...',
    'wifiRssi' => 'Waiting for hardware...',
    'uptime' => 'Waiting for hardware...',
    'freeHeap' => 'Waiting for hardware...',
    'battery' => 'Pending hardware integration',
    'transportStatus' => 'WAITING FOR DEVICE',
    'lastSeen' => 'Waiting for device...',
])

@php
    $statusLabel = $statusLabel === 'Waiting' && $isOnline ? 'ONLINE' : $statusLabel;
    $statusType = $statusType === 'standby' && $isOnline ? 'online' : $statusType;
@endphp

<div {{ $attributes->merge(['class' => 'p-6 rounded-3xl bg-white dark:bg-[#1E293B] border border-[#2D6A4F]/10 dark:border-white/10 shadow-sm space-y-4 hover:shadow-md transition-all']) }}>
    
    <!-- Card Header -->
    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-[#2D6A4F]/10 dark:bg-white/10 text-[#2D6A4F] dark:text-leaf-300 flex items-center justify-center font-bold text-base">
                📟
            </div>
            <div>
                <h3 class="text-base lg:text-lg font-bold text-[#1B4332] dark:text-slate-100">{{ $name }}</h3>
                <p class="text-sm sm:text-xs lg:text-sm lg:leading-5 font-mono text-[#2D6A4F] dark:text-leaf-300">{{ $deviceId }}</p>
            </div>
        </div>
        <x-leaf.status-badge :type="$statusType" :label="$statusLabel" />
    </div>

    <!-- Specs Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm sm:text-xs lg:text-sm lg:leading-5 max-sm:leading-5">
        <div class="p-3 rounded-2xl bg-gray-50 dark:bg-[#0F172A] border border-gray-100 dark:border-white/10">
            <span class="text-gray-400 dark:text-slate-500 block text-xs sm:text-[10px] lg:text-xs lg:leading-4 uppercase tracking-wider lg:tracking-normal font-semibold">Wi-Fi Signal</span>
            <span class="font-mono font-bold text-[#1B4332] dark:text-slate-100 text-sm">{{ is_numeric($wifiRssi) ? $wifiRssi.' dBm' : $wifiRssi }}</span>
        </div>
        <div class="p-3 rounded-2xl bg-gray-50 dark:bg-[#0F172A] border border-gray-100 dark:border-white/10">
            <span class="text-gray-400 dark:text-slate-500 block text-xs sm:text-[10px] lg:text-xs lg:leading-4 uppercase tracking-wider lg:tracking-normal font-semibold">Battery</span>
            <span class="font-mono font-bold text-[#2D6A4F] dark:text-leaf-300 text-sm">{{ $battery }}</span>
        </div>
        <div class="p-3 rounded-2xl bg-gray-50 dark:bg-[#0F172A] border border-gray-100 dark:border-white/10">
            <span class="text-gray-400 dark:text-slate-500 block text-xs sm:text-[10px] lg:text-xs lg:leading-4 uppercase tracking-wider lg:tracking-normal font-semibold">HTTP API</span>
            <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400 text-sm">{{ $transportStatus }}</span>
        </div>
        <div class="p-3 rounded-2xl bg-gray-50 dark:bg-[#0F172A] border border-gray-100 dark:border-white/10">
            <span class="text-gray-400 dark:text-slate-500 block text-xs sm:text-[10px] lg:text-xs lg:leading-4 uppercase tracking-wider lg:tracking-normal font-semibold">Firmware</span>
            <span class="font-mono font-medium text-gray-700 dark:text-slate-300 text-sm sm:text-xs lg:text-sm">{{ $firmware }}</span>
        </div>
        <div class="p-3 rounded-2xl bg-gray-50 dark:bg-[#0F172A] border border-gray-100 dark:border-white/10">
            <span class="text-gray-400 dark:text-slate-500 block text-xs sm:text-[10px] lg:text-xs lg:leading-4 uppercase tracking-wider lg:tracking-normal font-semibold">Free Heap</span>
            <span class="font-mono font-medium text-gray-700 dark:text-slate-300 text-sm sm:text-xs lg:text-sm">{{ is_numeric($freeHeap) ? $freeHeap.' B' : $freeHeap }}</span>
        </div>
        <div class="p-3 rounded-2xl bg-gray-50 dark:bg-[#0F172A] border border-gray-100 dark:border-white/10">
            <span class="text-gray-400 dark:text-slate-500 block text-xs sm:text-[10px] lg:text-xs lg:leading-4 uppercase tracking-wider lg:tracking-normal font-semibold">Uptime</span>
            <span class="font-mono font-medium text-gray-700 dark:text-slate-300 text-sm sm:text-xs lg:text-sm">{{ $uptime }}</span>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="pt-2 flex items-center justify-between border-t border-gray-100 dark:border-white/10 text-sm sm:text-xs lg:text-sm max-sm:leading-5">
        <span class="text-gray-400 dark:text-slate-500 text-xs sm:text-[11px] lg:text-xs lg:leading-4">Last Telemetry: <strong class="text-[#2D6A4F] dark:text-leaf-300 font-semibold">{{ $lastSeen }}</strong></span>
        <div class="flex items-center gap-2">
            <button type="button" class="px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-slate-700/60 hover:bg-[#2D6A4F] hover:text-white text-[#1B4332] dark:text-slate-100 font-semibold transition-all">
                Restart Node
            </button>
            <button type="button" class="px-3 py-1.5 rounded-xl bg-[#2D6A4F]/10 dark:bg-white/10 hover:bg-[#2D6A4F] hover:text-white text-[#2D6A4F] dark:text-leaf-300 font-semibold transition-all">
                Config
            </button>
        </div>
    </div>

</div>
