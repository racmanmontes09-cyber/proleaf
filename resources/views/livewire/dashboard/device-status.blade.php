<div wire:poll.visible.500ms="refreshDashboardLight" class="w-full max-w-full min-w-0 lg:h-full">
<div wire:ignore.self class="w-full max-w-full space-y-8 min-w-0 lg:h-full lg:flex lg:flex-col lg:overflow-y-auto lg:pb-2" x-data="leafDashboardCharts({
    activeTab: 'dashboard',
    hasChartTelemetry: {{ json_encode($hasChartTelemetry) }},
    deviceId: {{ json_encode($device?->id) }},
    useEchoTelemetry: {{ json_encode((bool) config('leaf.dashboard.live_chart.realtime_enabled', true)) }},
    pollingFallbackEnabled: {{ json_encode((bool) config('leaf.dashboard.live_chart.polling_fallback_enabled', false)) }},
    pollingUrl: {{ json_encode(route('dashboard.telemetry.readings', [], false)) }},
    maxPoints: {{ (int) config('leaf.dashboard.live_chart.max_points', 120) }},
    bufferPoints: {{ (int) config('leaf.dashboard.live_chart.buffer_points', 150) }},
    pollIntervalMs: {{ (int) config('leaf.dashboard.live_chart.poll_interval_ms', 1000) }},
    pollBatchLimit: {{ (int) config('leaf.dashboard.live_chart.poll_batch_limit', 120) }},
    debugTelemetryCharts: {{ json_encode((bool) config('leaf.dashboard.live_chart.debug', false)) }},
    deviceOnline: {{ json_encode($device?->is_online ?? false) }},
    onlineGraceMs: {{ \App\Models\Device::effectiveOnlineGraceSeconds() * 1000 }},
    thresholds: {
        temperatureLow: {{ json_encode($temperatureLowThreshold) }},
        temperatureHigh: {{ json_encode($temperatureHighThreshold) }},
        humidityLow: {{ json_encode($humidityLowThreshold) }},
        humidityHigh: {{ json_encode($humidityHighThreshold) }},
        waterTemperatureLow: {{ json_encode($waterTemperatureLowThreshold) }},
        waterTemperatureHigh: {{ json_encode($waterTemperatureHighThreshold) }},
        phLow: {{ json_encode($phLowThreshold) }},
        phHigh: {{ json_encode($phHighThreshold) }},
        ecLow: {{ json_encode($ecLowThreshold) }},
        ecHigh: {{ json_encode($ecHighThreshold) }},
        waterLevelLow: {{ json_encode($waterLevelLowThreshold) }},
        waterLevelHigh: {{ json_encode($waterLevelHighThreshold) }},
        waterFlowLow: {{ json_encode($waterFlowLowThreshold) }},
        waterFlowHigh: {{ json_encode($waterFlowHighThreshold) }},
    },
    initialKpis: {{ json_encode($telemetryKpis) }},
    initialChartPayload: {
        telemetryChartReadings: {{ json_encode($telemetryChartReadings) }},
        telemetryKpis: {{ json_encode($telemetryKpis) }},
        telemetryOverviewSeries: {{ json_encode($telemetryOverviewSeries) }},
        telemetryOverviewCategories: {{ json_encode($telemetryOverviewCategories) }},
        analyticsSeries: {{ json_encode($analyticsSeries) }},
        analyticsCategories: {{ json_encode($analyticsCategories) }},
        hasChartTelemetry: {{ json_encode($hasChartTelemetry) }},
    },
})" x-init="initApexCharts()" x-effect="activeTab; $dispatch('active-tab-changed', activeTab);" x-on:dashboard-chart-data-updated.window="updateApexCharts($event.detail)" x-on:dashboard-device-selected.window="switchTelemetryDevice($event.detail.deviceId)" x-on:dashboard-device-status-updated.window="deviceOnline = $event.detail.isOnline; if (!$event.detail.isOnline) applyOfflineStatus()" x-on:switch-tab.window="activeTab = $event.detail">

 

    <!-- ========================================== -->
    <!-- TAB CONTENT 1: DASHBOARD OVERVIEW           -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'dashboard'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-3 sm:space-y-8 min-w-0">
        <!-- KPI METRICS GRID -->
        <div wire:ignore class="grid grid-cols-3 sm:grid-cols-[repeat(auto-fit,minmax(min(100%,13.5rem),1fr))] gap-1 max-sm:gap-0.5 sm:gap-4 xl:gap-5 min-w-0 items-stretch">
            
            @if ($prefs['kpi_cards']['air_temperature'] ?? true)
            <!-- Air Temp -->
            <x-leaf.kpi-card 
                title="Air Temp" 
                sensorKey="air_temperature"
                value="{{ $temperatureValue }}" 
                unit="°C" 
                :status="$temperatureStatusLabel" 
                :statusType="$temperatureStatusType" 
                target="{{ $temperatureTargetLabel }}"
                :trend="$temperatureTrendText"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"/>
                    </svg>
                </x-slot:icon>
            </x-leaf.kpi-card>
            @endif

            @if ($prefs['kpi_cards']['ph'] ?? true)
            <!-- Water pH -->
            <x-leaf.kpi-card 
                title="Water pH" 
                sensorKey="ph"
                value="{{ $phValue }}" 
                unit="pH" 
                :status="$phStatusLabel" 
                :statusType="$phStatusType" 
                target="{{ $phTargetLabel }}"
                :trend="$phTrendText"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 3h15"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 3v16a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V3"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 14h12"/>
                    </svg>
                </x-slot:icon>
            </x-leaf.kpi-card>
            @endif

            @if ($prefs['kpi_cards']['ec'] ?? true)
            <!-- EC -->
            <x-leaf.kpi-card 
                title="Nutrient EC" 
                sensorKey="ec"
                value="{{ $ecValue }}" 
                unit="mS/cm" 
                :status="$ecStatusLabel" 
                :statusType="$ecStatusType" 
                target="{{ $ecTargetLabel }}"
                :trend="$ecTrendText"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.5 7.75L7.5 13.75h3L8.5 17.75 16.5 11.25h-3L12.5 7.75z"/>
                    </svg>
                </x-slot:icon>
            </x-leaf.kpi-card>
            @endif

            @if ($prefs['kpi_cards']['water_temperature'] ?? true)
            <!-- Water Temp -->
            <x-leaf.kpi-card 
                title="Water Temp" 
                sensorKey="water_temperature"
                value="{{ $waterTemperatureValue }}" 
                unit="°C" 
                :status="$waterTemperatureStatusLabel" 
                :statusType="$waterTemperatureStatusType" 
                :trend="$waterTemperatureTrendText"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v9.5"/>
                        <circle stroke-linecap="round" stroke-linejoin="round" stroke-width="2" cx="12" cy="16" r="2"/>
                    </svg>
                </x-slot:icon>
            </x-leaf.kpi-card>
            @endif

            @if ($prefs['kpi_cards']['water_level'] ?? true)
            <!-- Water Level -->
            <x-leaf.kpi-card 
                title="Water Level" 
                sensorKey="water_level"
                value="{{ $waterLevelValue }}" 
                unit="%" 
                :status="$waterLevelStatusLabel" 
                :statusType="$waterLevelStatusType" 
                :trend="$waterLevelTrendText"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <ellipse stroke-linecap="round" stroke-linejoin="round" stroke-width="2" cx="12" cy="5" rx="9" ry="3"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5v14a9 3 0 0 0 18 0V5"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 14h18"/>
                    </svg>
                </x-slot:icon>
            </x-leaf.kpi-card>
            @endif

            @if ($prefs['kpi_cards']['water_flow'] ?? true)
            <!-- Water Flow -->
            <x-leaf.kpi-card 
                title="Water Flow" 
                sensorKey="water_flow"
                value="{{ $waterFlowValue }}" 
                unit="L/min" 
                :status="$waterFlowStatusLabel" 
                :statusType="$waterFlowStatusType" 
                :trend="$waterFlowTrendText"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12q2.5 2 5 0t5 0 5 0 5 0"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 19q2.5 2 5 0t5 0 5 0 5 0"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 5q2.5 2 5 0t5 0 5 0 5 0"/>
                    </svg>
                </x-slot:icon>
            </x-leaf.kpi-card>
            @endif

        </div>

        <!-- MAIN DASHBOARD CONTENT GRID -->
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 lg:gap-6 min-w-0 items-start">
            
            @if ($prefs['show_chart'] ?? true)
            <!-- Left Column: 24h ApexCharts Telemetry Card (8 Cols) -->
                <div class="xl:col-span-8 p-6 max-sm:p-3 sm:p-8 rounded-3xl bg-white dark:bg-[#1E293B] border border-[#2D6A4F]/10 dark:border-white/10 shadow-sm space-y-6 max-sm:space-y-3 min-w-0 overflow-hidden flex flex-col justify-between">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100 dark:border-white/10 min-w-0">
                    <div class="min-w-0">
                        <p class="text-xs lg:text-sm lg:leading-5 max-sm:text-[12px] max-sm:leading-5 text-[#1B4332]/70 dark:text-slate-400 truncate">Showing: <span x-text="historicalRangeLabel()"></span></p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span x-show="historicalFilterError" x-text="historicalFilterError" class="text-xs lg:text-sm lg:leading-5 font-semibold text-rose-600 dark:text-rose-400"></span>
                        <label for="historical-range-filter" class="sr-only">Filter date range</label>
                        <select id="historical-range-filter" x-model="historicalPreset" @change="setHistoricalPreset(historicalPreset)" class="px-3 py-1.5 max-sm:px-1.5 max-sm:py-1 rounded-lg border border-[#2D6A4F]/20 dark:border-white/15 bg-white dark:bg-[#0F172A] text-xs lg:text-sm lg:leading-5 max-sm:text-[12px] max-sm:leading-5 font-semibold text-[#1B4332] dark:text-slate-200 focus:border-[#2D6A4F] focus:outline-none focus:ring-2 focus:ring-[#95D5B2]/50">
                            <option value="today">Today</option>
                            <option value="24h">Last 24 Hours</option>
                            <option value="7d">Last 7 Days</option>
                            <option value="30d">Last 30 Days</option>
                            <option value="custom">Custom Range</option>
                        </select>
                        <label for="overview-sensor-filter" class="sr-only">Filter sensor readings</label>
                        <select id="overview-sensor-filter" x-model="selectedOverviewSensor" @change="filterOverviewSensor()" class="px-3 py-1.5 max-sm:px-1.5 max-sm:py-1 rounded-lg border border-[#2D6A4F]/20 dark:border-white/15 bg-white dark:bg-[#0F172A] text-xs lg:text-sm lg:leading-5 max-sm:text-[12px] max-sm:leading-5 font-semibold text-[#1B4332] dark:text-slate-200 focus:border-[#2D6A4F] focus:outline-none focus:ring-2 focus:ring-[#95D5B2]/50">
                            <option value="all">All sensors</option>
                            <option value="Water pH">Water pH</option>
                            <option value="Water Temp (°C)">Water Temp (°C)</option>
                            <option value="Nutrient EC (mS)">Nutrient EC (mS)</option>
                            <option value="Air Temp (°C)">Air Temp (°C)</option>
                            <option value="Water Flow (L/min)">Water Flow (L/min)</option>
                            <option value="Water Level (%)">Water Level (%)</option>
                        </select>
                    </div>
                </div>

                <div wire:ignore id="telemetryOverviewChart" class="w-full h-[280px] sm:h-[320px] lg:h-[340px] min-h-[280px] overflow-hidden relative">
                    <div x-show="!hasChartTelemetry" class="absolute inset-0 flex items-center justify-center text-sm max-sm:text-[12px] max-sm:leading-5 font-medium text-[#1B4332]/70 dark:text-slate-400 bg-white/80 dark:bg-[#1E293B]/80 z-10 pointer-events-none">
                        Waiting for sensor data...
                    </div>
                    <button x-show="!isLive" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2" @click="goToLive()" type="button" class="absolute bottom-3 right-3 z-20 flex items-center gap-1.5 px-3 py-1.5 max-sm:px-1.5 max-sm:py-1 rounded-full bg-[#2D6A4F] text-white text-xs lg:text-sm lg:leading-5 max-sm:text-[12px] max-sm:leading-5 font-bold shadow-lg hover:bg-[#1B4332] transition-colors cursor-pointer">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Go to Live
                    </button>
                </div>
            </div>
            @endif

            <!-- Right Column: Node Specs & Relays (4 Cols) -->
            <div class="{{ ($prefs['show_chart'] ?? true) ? 'xl:col-span-4' : 'xl:col-span-12' }} flex flex-col gap-6 min-w-0">

                @if ($prefs['show_controller_card'] ?? true)
                <!-- Controller Card -->
                <div class="min-h-[233px] p-3 max-sm:p-2 rounded-3xl bg-white dark:bg-[#1E293B] border border-[#2D6A4F]/10 dark:border-white/10 shadow-sm space-y-2.5 max-sm:space-y-1.5 min-w-0 shrink-0 flex flex-col">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-white/10 min-w-0 shrink-0">
                        <h3 class="text-[11px] lg:text-base lg:leading-6 max-sm:text-[12px] max-sm:leading-4 font-bold text-[#1B4332] dark:text-slate-100 truncate">ESP32 Controller Node</h3>
                        <x-leaf.status-badge :type="$deviceStatusType" :label="$deviceStatusLabel" class="shrink-0" />
                    </div>

                    <div class="space-y-1.5 text-xs lg:text-sm lg:leading-6 max-sm:text-[11px] leading-5 max-sm:leading-4">
                        <div class="flex justify-between py-1 border-b border-gray-50 dark:border-white/5 min-w-0">
                            <span class="text-gray-500 dark:text-slate-400">Device Name</span>
                            <span class="font-mono font-bold text-[#2D6A4F] dark:text-leaf-300 truncate">{{ $deviceNameLabel }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50 dark:border-white/5 min-w-0">
                            <span class="text-gray-500 dark:text-slate-400">Last Seen</span>
                            <span class="font-mono text-gray-700 dark:text-slate-300 truncate">{{ $lastSeenLabel }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50 dark:border-white/5 min-w-0">
                            <span class="text-gray-500 dark:text-slate-400">IP</span>
                            <span class="font-mono text-[#2D6A4F] dark:text-leaf-300 font-semibold truncate">{{ $localIpLabel }}</span>
                        </div>
                        <div class="flex justify-between py-1 min-w-0">
                            <span class="text-gray-500 dark:text-slate-400">RSSI</span>
                            <span class="font-mono text-[#2D6A4F] dark:text-leaf-300 font-semibold truncate">{{ $wifiRssiLabel }}</span>
                        </div>
                    </div>
                </div>
                @endif

                @if ($prefs['show_actuator_relays'] ?? true)
                <!-- Relay Actuators -->
                <div class="h-[233px] p-3.5 max-sm:p-2 rounded-3xl bg-white dark:bg-[#1E293B] border border-[#2D6A4F]/10 dark:border-white/10 shadow-sm space-y-2.5 max-sm:space-y-1.5 min-w-0 flex flex-col" x-data x-effect="if ($wire.commandStatusMessage) { setTimeout(() => { $wire.set('commandStatusMessage', '') }, 3000) }">
                    <div class="flex items-center justify-between gap-2 shrink-0 min-w-0">
                        <h3 class="text-[11px] lg:text-base lg:leading-6 max-sm:text-[12px] max-sm:leading-4 font-bold text-[#1B4332] dark:text-slate-100">Actuator Relays</h3>
                        <div class="ml-auto flex items-center justify-end gap-2 min-w-0">
                            @if ($commandStatusMessage)
                                <span class="text-[10px] lg:text-xs lg:leading-4 leading-3 text-[#40916C] dark:text-leaf-300 font-medium truncate max-w-[7rem] sm:max-w-[60%]">{{ $commandStatusMessage }}</span>
                            @endif
                            <span class="shrink-0 rounded-full border border-[#2D6A4F]/10 dark:border-white/10 bg-[#95D5B2]/25 dark:bg-leaf-300/15 px-2 py-0.5 text-[10px] lg:text-xs lg:leading-4 max-sm:text-[10px] max-sm:leading-3 font-semibold text-[#2D6A4F] dark:text-leaf-300 whitespace-nowrap">{{ $actuatorOnCount }} ON</span>
                        </div>
                    </div>
                    <div class="space-y-2 min-w-0 flex-1 overflow-y-auto">
                        @foreach ($actuatorCards as $actuatorCard)
                            <div class="p-2.5 max-sm:p-1.5 rounded-2xl bg-[#F8FAF8] dark:bg-[#0F172A] border border-[#2D6A4F]/10 dark:border-white/10 flex items-center justify-between min-w-0">
                                <div class="min-w-0">
                                    <p class="text-xs lg:text-sm lg:leading-5 max-sm:text-[12px] max-sm:leading-5 text-[#1B4332] dark:text-slate-100 truncate">{{ $actuatorCard['name'] }}</p>
                                    <p class="text-xs lg:text-[13px] lg:leading-5 max-sm:text-[12px] max-sm:leading-5 text-gray-500 dark:text-slate-400 truncate">{{ $actuatorCard['detail'] }}</p>
                                </div>
                                <button
                                    wire:click="toggleActuator('{{ $actuatorCard['command'] }}')"
                                    wire:loading.attr="disabled"
                                    class="px-3 py-1 max-sm:px-1.5 max-sm:py-0.5 text-[10px] lg:text-xs lg:leading-4 max-sm:text-[10px] max-sm:leading-3 font-semibold rounded-full shrink-0 transition cursor-pointer
                                        {{ $actuatorCard['statusType'] === 'online'
                                            ? 'bg-[#2D6A4F] text-white hover:bg-[#1B4332]'
                                            : ($actuatorCard['statusType'] === 'warning'
                                                ? 'bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-500/20 dark:text-amber-300 dark:hover:bg-amber-500/30'
                                                : 'bg-[#95D5B2]/40 text-[#1B4332] hover:bg-[#95D5B2]/60 dark:bg-leaf-300/25 dark:text-leaf-200 dark:hover:bg-leaf-300/40') }}
                                        disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ $actuatorCard['status'] }}
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>

        </div>

     

    </div>

   

    <!-- ========================================== -->
    <!-- TAB CONTENT 6: SYSTEM SETTINGS             -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'settings'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8 min-w-0">
        
        <x-leaf.page-header 
            title="System Configurations & Target Setpoints" 
            subtitle="Define target thresholds for hydroponic environmental conditions and dosing intervals."
            badge="Lettuce Mode"
        />

        <div class="p-6 sm:p-8 rounded-3xl bg-white dark:bg-[#1E293B] border border-[#2D6A4F]/10 dark:border-white/10 shadow-sm space-y-6 min-w-0">
            <h3 class="text-base sm:text-lg lg:text-xl lg:leading-7 font-bold text-[#1B4332] dark:text-slate-100">Target Environmental Setpoints</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 min-w-0">
                <div class="space-y-2 min-w-0">
                    <label class="block text-xs lg:text-sm lg:leading-5 font-bold text-[#1B4332] dark:text-slate-200 uppercase">Target Water pH Min / Max</label>
                    <div class="flex gap-2">
                        <input type="text" value="{{ $phLowThreshold !== null ? $phLowThreshold : 'Not available' }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 dark:bg-[#0F172A] text-xs lg:text-sm lg:leading-5 font-bold text-[#1B4332] dark:text-slate-200" readonly>
                        <input type="text" value="{{ $phHighThreshold !== null ? $phHighThreshold : 'Not available' }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 dark:bg-[#0F172A] text-xs lg:text-sm lg:leading-5 font-bold text-[#1B4332] dark:text-slate-200" readonly>
                    </div>
                </div>

                <div class="space-y-2 min-w-0">
                    <label class="block text-xs lg:text-sm lg:leading-5 font-bold text-[#1B4332] dark:text-slate-200 uppercase">Target EC Range (mS/cm)</label>
                    <div class="flex gap-2">
                        <input type="text" value="{{ $ecLowThreshold !== null ? $ecLowThreshold : 'Not available' }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 dark:bg-[#0F172A] text-xs lg:text-sm lg:leading-5 font-bold text-[#1B4332] dark:text-slate-200" readonly>
                        <input type="text" value="{{ $ecHighThreshold !== null ? $ecHighThreshold : 'Not available' }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 dark:bg-[#0F172A] text-xs lg:text-sm lg:leading-5 font-bold text-[#1B4332] dark:text-slate-200" readonly>
                    </div>
                </div>

                <div class="space-y-2 min-w-0">
                    <label class="block text-xs lg:text-sm lg:leading-5 font-bold text-[#1B4332] dark:text-slate-200 uppercase">Air Temp Target (°C)</label>
                    <div class="flex gap-2">
                        <input type="text" value="{{ $temperatureLowThreshold !== null ? $temperatureLowThreshold : 'Not available' }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 dark:bg-[#0F172A] text-xs lg:text-sm lg:leading-5 font-bold text-[#1B4332] dark:text-slate-200" readonly>
                        <input type="text" value="{{ $temperatureHighThreshold !== null ? $temperatureHighThreshold : 'Not available' }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 dark:bg-[#0F172A] text-xs lg:text-sm lg:leading-5 font-bold text-[#1B4332] dark:text-slate-200" readonly>
                    </div>
                </div>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="button" class="px-6 py-3 rounded-xl bg-[#2D6A4F] text-white font-bold text-xs lg:text-sm lg:leading-5 hover:bg-[#1B4332] transition-colors shadow-md">Save Setpoints</button>
            </div>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- TAB CONTENT 7: HISTORICAL SENSOR DATA       -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'reports'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8 min-w-0">
        
        <x-leaf.page-header 
            title="Historical Sensor Data" 
            subtitle="Review historical telemetry and sensor data trends."
            badge="Telemetry"
        />

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 lg:gap-6 min-w-0 items-start">
            <div class="xl:col-span-12 p-6 sm:p-8 rounded-3xl bg-white dark:bg-[#1E293B] border border-[#2D6A4F]/10 dark:border-white/10 shadow-sm space-y-4 min-w-0 overflow-hidden flex flex-col justify-between">
                <h3 class="text-base sm:text-lg lg:text-xl lg:leading-7 font-bold text-[#1B4332] dark:text-slate-100">Telemetry History & Sensor Data Trends</h3>
                <div wire:ignore id="telemetryHistoryChart" class="w-full h-[260px] sm:h-[280px] lg:h-[320px] min-h-[260px] overflow-hidden relative">
                    <div x-show="!hasChartTelemetry" class="absolute inset-0 flex items-center justify-center text-sm font-medium text-[#1B4332]/70 dark:text-slate-400 bg-white/80 dark:bg-[#1E293B]/80 z-10 pointer-events-none">
                        No historical readings for this range.
                    </div>
                    <button x-show="!isLive" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2" @click="goToLive()" type="button" class="absolute bottom-3 right-3 z-20 flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#2D6A4F] text-white text-xs lg:text-sm lg:leading-5 font-bold shadow-lg hover:bg-[#1B4332] transition-colors cursor-pointer">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Go to Live
                    </button>
                </div>
            </div>

        </div>

    </div>

</div>
</div>
