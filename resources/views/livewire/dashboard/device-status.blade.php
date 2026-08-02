<div wire:poll.visible.5s="refreshDashboardLight" class="space-y-8 min-w-0" x-data="{
    activeTab: 'dashboard',
    hasChartTelemetry: {{ json_encode($hasChartTelemetry) }},
    initialized: false,
    subscribed: false,
    charts: {
        telemetryOverview: null,
        analytics: null
    },
    overviewData: {
        categories: [],
        ph: [],
        waterTemp: [],
        ec: []
    },
    analyticsData: {
        categories: [],
        airTemp: [],
        humidity: [],
        waterFlow: []
    },
    initialChartPayload() {
        return {
            telemetryOverviewSeries: {{ json_encode($telemetryOverviewSeries) }},
            telemetryOverviewCategories: {{ json_encode($telemetryOverviewCategories) }},
            analyticsSeries: {{ json_encode($analyticsSeries) }},
            analyticsCategories: {{ json_encode($analyticsCategories) }},
            hasChartTelemetry: {{ json_encode($hasChartTelemetry) }},
        };
    },
    initApexCharts() {
        this.$nextTick(() => {
            this.renderOrUpdateCharts(this.initialChartPayload());
            this.listenToPusher();
        });
    },
    listenToPusher() {
        if (typeof window.Echo !== 'undefined' && !this.subscribed) {
            this.subscribed = true;
            const channel = window.Echo.channel('telemetry');
            const handler = (data) => {
                this.handleTelemetryReceived(data);
            };
            channel.listen('.TelemetryReceived', handler);
            channel.listen('TelemetryReceived', handler);
        }
    },
    handleTelemetryReceived(data) {
        if (!data) return;
        this.hasChartTelemetry = true;

        let timeLabel = '';
        if (data.measured_at) {
            const dt = new Date(data.measured_at);
            if (!isNaN(dt.getTime())) {
                timeLabel = dt.toLocaleTimeString([], { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
            } else {
                timeLabel = data.measured_at;
            }
        } else {
            timeLabel = new Date().toLocaleTimeString([], { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }

        const ph = data.ph !== null && data.ph !== undefined ? parseFloat(data.ph) : null;
        const wTemp = data.water_temperature !== null && data.water_temperature !== undefined ? parseFloat(data.water_temperature) : null;
        const ec = data.ec !== null && data.ec !== undefined ? parseFloat(data.ec) : null;
        const aTemp = data.air_temperature !== null && data.air_temperature !== undefined ? parseFloat(data.air_temperature) : null;
        const hum = data.humidity !== null && data.humidity !== undefined ? parseFloat(data.humidity) : null;
        const wFlow = data.water_flow !== null && data.water_flow !== undefined ? parseFloat(data.water_flow) : null;

        // 1. Dashboard Overview Data
        this.overviewData.categories.push(timeLabel);
        this.overviewData.ph.push(ph);
        this.overviewData.waterTemp.push(wTemp);
        this.overviewData.ec.push(ec);

        if (this.overviewData.categories.length > 100) {
            this.overviewData.categories.shift();
            this.overviewData.ph.shift();
            this.overviewData.waterTemp.shift();
            this.overviewData.ec.shift();
        }

        if (this.charts.telemetryOverview) {
            this.charts.telemetryOverview.updateSeries([
                { name: 'Water pH', data: [...this.overviewData.ph] },
                { name: 'Water Temp (°C)', data: [...this.overviewData.waterTemp] },
                { name: 'Nutrient EC (mS)', data: [...this.overviewData.ec] }
            ], true);
            this.charts.telemetryOverview.updateOptions({
                xaxis: { categories: [...this.overviewData.categories] }
            }, false, true);
        }

        // 2. Analytics Data
        this.analyticsData.categories.push(timeLabel);
        this.analyticsData.airTemp.push(aTemp);
        this.analyticsData.humidity.push(hum);
        this.analyticsData.waterFlow.push(wFlow);

        if (this.analyticsData.categories.length > 100) {
            this.analyticsData.categories.shift();
            this.analyticsData.airTemp.shift();
            this.analyticsData.humidity.shift();
            this.analyticsData.waterFlow.shift();
        }

        if (this.charts.analytics) {
            this.charts.analytics.updateSeries([
                { name: 'Air Temp (°C)', type: 'column', data: [...this.analyticsData.airTemp] },
                { name: 'Humidity (%)', type: 'line', data: [...this.analyticsData.humidity] },
                { name: 'Water Flow (L/min)', type: 'line', data: [...this.analyticsData.waterFlow] }
            ], true);
            this.charts.analytics.updateOptions({
                xaxis: { categories: [...this.analyticsData.categories] }
            }, false, true);
        }
    },
    updateApexCharts(payload) {
        if (this.initialized) return;
        this.$nextTick(() => this.renderOrUpdateCharts(payload || this.initialChartPayload()));
    },
    renderOrUpdateCharts(payload) {
        if (typeof ApexCharts === 'undefined') return;

        const telemetryOverviewSeries = payload.telemetryOverviewSeries || [];
        const telemetryOverviewCategories = payload.telemetryOverviewCategories || [];
        const analyticsSeries = payload.analyticsSeries || [];
        const analyticsCategories = payload.analyticsCategories || [];
        // yield charts removed per scope limitations

        if (payload.hasChartTelemetry !== undefined) {
            this.hasChartTelemetry = Boolean(payload.hasChartTelemetry);
        }

        if (!this.initialized) {
            this.overviewData.categories = (telemetryOverviewCategories || []).slice(-100);
            this.overviewData.ph = (telemetryOverviewSeries[0]?.data || []).slice(-100);
            this.overviewData.waterTemp = (telemetryOverviewSeries[1]?.data || []).slice(-100);
            this.overviewData.ec = (telemetryOverviewSeries[2]?.data || []).slice(-100);

            this.analyticsData.categories = (analyticsCategories || []).slice(-100);
            this.analyticsData.airTemp = (analyticsSeries[0]?.data || []).slice(-100);
            this.analyticsData.humidity = (analyticsSeries[1]?.data || []).slice(-100);
            this.analyticsData.waterFlow = (analyticsSeries[2]?.data || []).slice(-100);

            const chartEl = document.querySelector('#telemetryOverviewChart');
            if (chartEl && !this.charts.telemetryOverview) {
                const telemetryOverviewOptions = {
                    series: [
                        { name: 'Water pH', data: [...this.overviewData.ph] },
                        { name: 'Water Temp (°C)', data: [...this.overviewData.waterTemp] },
                        { name: 'Nutrient EC (mS)', data: [...this.overviewData.ec] }
                    ],
                    chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
                    colors: ['#2D6A4F', '#40916C', '#95D5B2'],
                    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] } },
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 2.5 },
                    xaxis: { categories: [...this.overviewData.categories], labels: { style: { colors: '#1B4332' } } },
                    yaxis: { labels: { style: { colors: '#1B4332' } } },
                    grid: { borderColor: '#E5E7EB', strokeDashArray: 4 },
                    legend: { position: 'top', horizontalAlign: 'right' }
                };
                this.charts.telemetryOverview = new ApexCharts(chartEl, telemetryOverviewOptions);
                this.charts.telemetryOverview.render();
            }

            const analyticsEl = document.querySelector('#analyticsMultiChart');
            if (analyticsEl && !this.charts.analytics) {
                const analyticsOptions = {
                    series: [
                        { name: 'Air Temp (°C)', type: 'column', data: [...this.analyticsData.airTemp] },
                        { name: 'Humidity (%)', type: 'line', data: [...this.analyticsData.humidity] },
                        { name: 'Water Flow (L/min)', type: 'line', data: [...this.analyticsData.waterFlow] }
                    ],
                    chart: { height: 320, type: 'line', toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
                    stroke: { width: [0, 3, 3], curve: 'smooth' },
                    colors: ['#D8F3DC', '#2D6A4F', '#40916C'],
                    plotOptions: { bar: { columnWidth: '40%', borderRadius: 6 } },
                    labels: [...this.analyticsData.categories],
                    xaxis: { type: 'category' },
                    grid: { borderColor: '#F1F5F9' }
                };
                this.charts.analytics = new ApexCharts(analyticsEl, analyticsOptions);
                this.charts.analytics.render();
            }

            // yieldDistribution chart intentionally removed per scope

            this.initialized = true;
        }
    }
}" x-init="initApexCharts()" x-effect="activeTab; $dispatch('active-tab-changed', activeTab);" x-on:dashboard-chart-data-updated.window="updateApexCharts($event.detail)" x-on:switch-tab.window="activeTab = $event.detail">

 

    <!-- ========================================== -->
    <!-- TAB CONTENT 1: DASHBOARD OVERVIEW           -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'dashboard'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8 min-w-0">
        
        

        <!-- KPI METRICS GRID -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7 gap-4 sm:gap-5 min-w-0 items-stretch">
            
            <!-- Air Temp -->
            <x-leaf.kpi-card 
                title="Air Temp" 
                value="{{ $temperatureValue }}" 
                unit="°C" 
                :status="$temperatureStatusLabel" 
                :statusType="$temperatureStatusType" 
                target="{{ $temperatureTargetLabel }}"
                :trend="$temperatureTrendText"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </x-slot:icon>
            </x-leaf.kpi-card>

            <!-- Air Humidity -->
            <x-leaf.kpi-card 
                title="Air Humidity" 
                value="{{ $humidityValue }}" 
                unit="%" 
                :status="$humidityStatusLabel" 
                :statusType="$humidityStatusType" 
                target="{{ $humidityTargetLabel }}"
                :trend="$humidityTrendText"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 001.059-9.891A7.001 7.001 0 005.999 7H5a5 5 0 00-2 9.9v.1z"/>
                    </svg>
                </x-slot:icon>
            </x-leaf.kpi-card>

            <!-- Water Temp -->
            <x-leaf.kpi-card 
                title="Water Temp" 
                value="{{ $waterTemperatureValue }}" 
                unit="°C" 
                :status="$waterTemperatureStatusLabel" 
                :statusType="$waterTemperatureStatusType" 
                target="{{ $waterTemperatureTargetLabel }}"
                :trend="$waterTemperatureTrendText"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </x-slot:icon>
            </x-leaf.kpi-card>

            <!-- Water pH -->
            <x-leaf.kpi-card 
                title="Water pH" 
                value="{{ $phValue }}" 
                unit="pH" 
                :status="$phStatusLabel" 
                :statusType="$phStatusType" 
                target="{{ $phTargetLabel }}"
                :trend="$phTrendText"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </x-slot:icon>
            </x-leaf.kpi-card>

            <!-- EC -->
            <x-leaf.kpi-card 
                title="Nutrient EC" 
                value="{{ $ecValue }}" 
                unit="mS/cm" 
                :status="$ecStatusLabel" 
                :statusType="$ecStatusType" 
                target="{{ $ecTargetLabel }}"
                :trend="$ecTrendText"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </x-slot:icon>
            </x-leaf.kpi-card>

            <!-- Water Level -->
            <x-leaf.kpi-card 
                title="Water Level" 
                value="{{ $waterLevelValue }}" 
                unit="%" 
                :status="$waterLevelStatusLabel" 
                :statusType="$waterLevelStatusType" 
                target="{{ $waterLevelTargetLabel }}"
                :trend="$waterLevelTrendText"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </x-slot:icon>
            </x-leaf.kpi-card>

            <!-- Water Flow -->
            <x-leaf.kpi-card 
                title="Water Flow" 
                value="{{ $waterFlowValue }}" 
                unit="L/min" 
                :status="$waterFlowStatusLabel" 
                :statusType="$waterFlowStatusType" 
                target="{{ $waterFlowTargetLabel }}"
                :trend="$waterFlowTrendText"
            >
                <x-slot:icon>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </x-slot:icon>
            </x-leaf.kpi-card>

        </div>

        <!-- MAIN DASHBOARD CONTENT GRID -->
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 lg:gap-6 min-w-0 items-start">
            
            <!-- Left Column: 24h ApexCharts Telemetry Card (8 Cols) -->
            <div class="xl:col-span-8 p-6 sm:p-8 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-6 min-w-0 overflow-hidden flex flex-col justify-between">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100 min-w-0">
                    <div class="min-w-0">
                        <h3 class="text-lg font-bold text-[#1B4332] truncate">24-Hour Telemetry Analytics</h3>
                        <p class="text-xs text-[#1B4332]/70 truncate">Solution pH, Water Temp (°C) and Electrical Conductivity (EC)</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#95D5B2]/30 text-[#1B4332] shrink-0">
                        Live HTTP API Feed
                    </span>
                </div>

                <div wire:ignore id="telemetryOverviewChart" class="w-full h-[280px] sm:h-[320px] lg:h-[340px] min-h-[280px] overflow-hidden relative">
                    <div x-show="!hasChartTelemetry" class="absolute inset-0 flex items-center justify-center text-sm font-medium text-[#1B4332]/70 bg-white/80 z-10 pointer-events-none">
                        Waiting for sensor data...
                    </div>
                </div>
            </div>

            <!-- Right Column: Node Specs & Relays (4 Cols) -->
            <div class="xl:col-span-4 space-y-6 min-w-0">
                
                <!-- Controller Card -->
                <div class="p-3 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-2.5 min-w-0">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100 min-w-0">
                        <h3 class="text-[11px] font-bold text-[#1B4332] truncate">ESP32 Controller Node</h3>
                        <x-leaf.status-badge :type="$deviceStatusType" :label="$deviceStatusLabel" class="shrink-0" />
                    </div>

                    <div class="space-y-1.5 text-[10px] leading-5">
                        <div class="flex justify-between py-1 border-b border-gray-50 min-w-0">
                            <span class="text-gray-500">Device Name</span>
                            <span class="font-mono font-bold text-[#2D6A4F] truncate">{{ $deviceNameLabel }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50 min-w-0">
                            <span class="text-gray-500">Last Seen</span>
                            <span class="font-mono text-gray-700 truncate">{{ $lastSeenLabel }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50 min-w-0">
                            <span class="text-gray-500">Firmware</span>
                            <span class="font-mono text-gray-700 truncate">{{ $firmwareLabel }}</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-50 min-w-0">
                            <span class="text-gray-500">IP</span>
                            <span class="font-mono text-[#2D6A4F] font-semibold truncate">{{ $localIpLabel }}</span>
                        </div>
                        <div class="flex justify-between py-1 min-w-0">
                            <span class="text-gray-500">RSSI</span>
                            <span class="font-mono text-[#2D6A4F] font-semibold truncate">{{ $wifiRssiLabel }}</span>
                        </div>
                    </div>
                </div>

                <!-- Relay Actuators -->
                <div class="p-3.5 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-2.5 min-w-0">
                    <h3 class="text-[11px] font-bold text-[#1B4332]">Actuator Relays</h3>
                    <div class="space-y-2 min-w-0">
                        @foreach ($actuatorCards as $actuatorCard)
                            <div class="p-2.5 rounded-2xl bg-[#F8FAF8] border border-[#2D6A4F]/10 flex items-center justify-between min-w-0">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold text-[#1B4332] truncate">{{ $actuatorCard['name'] }}</p>
                                    <p class="text-[10px] text-gray-500 truncate">{{ $actuatorCard['detail'] }}</p>
                                </div>
                                <span class="px-2 py-0.5 text-[8px] font-semibold rounded-full shrink-0 {{ $actuatorCard['statusType'] === 'online' ? 'bg-[#2D6A4F] text-white' : ($actuatorCard['statusType'] === 'warning' ? 'bg-amber-100 text-amber-800' : 'bg-[#95D5B2]/40 text-[#1B4332]') }}">{{ $actuatorCard['status'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

        </div>

     

    </div>

    <!-- ========================================== -->
    <!-- TAB CONTENT 2: MONITORING HUB              -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'monitoring'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8 min-w-0">
        
        <x-leaf.page-header 
            title="Real-Time Telemetry & Sensor Gauges" 
            subtitle="Live precision sensors monitoring ambient atmospheric and hydroponic water solution variables."
            badge="{{ $monitoringSensorsBadgeLabel }}"
        />

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 min-w-0">
            @forelse ($monitoringSensors as $sensor)
                <x-leaf.sensor-card 
                    name="{{ $sensor['name'] }}"
                    value="{{ $sensor['value'] }}"
                    unit="{{ $sensor['unit'] }}"
                    status="{{ $sensor['status'] }}"
                    statusType="{{ $sensor['statusType'] }}"
                    min="{{ $sensor['min'] }}"
                    max="{{ $sensor['max'] }}"
                    percentage="{{ $sensor['percentage'] }}"
                    optimalRange="{{ $sensor['optimalRange'] }}"
                    lastCalibrated="{{ $sensor['lastCalibrated'] }}"
                />
            @empty
                <x-leaf.sensor-card 
                    name="Waiting for sensor data..."
                    value="--"
                    unit=""
                    status="Waiting"
                    statusType="standby"
                    min="Not available"
                    max="Not available"
                    percentage="0"
                    optimalRange="Not available"
                    lastCalibrated="Waiting for sensor data..."
                />
            @endforelse
        </div>

        <!-- Sensor Diagnostics Table -->
        <div class="space-y-4 min-w-0">
            <h3 class="text-lg font-bold text-[#1B4332]">Sensor Hardware Calibration & ADC Signals</h3>
            <x-leaf.table :headers="['Sensor Name', 'Hardware Pin', 'Raw ADC Voltage', 'Offset Drift', 'Signal Quality', 'Status', 'Actions']">
                <tr>
                    <td class="px-6 py-4 font-bold text-[#1B4332]">Analog pH Probe</td>
                    <td class="px-6 py-4 font-mono text-[#2D6A4F]">Pending hardware mapping</td>
                    <td class="px-6 py-4 font-mono">Pending hardware integration</td>
                    <td class="px-6 py-4 font-mono text-emerald-600">Pending hardware integration</td>
                    <td class="px-6 py-4 font-semibold text-[#2D6A4F]">Pending hardware integration</td>
                    <td class="px-6 py-4"><x-leaf.status-badge type="standby" label="Pending" /></td>
                    <td class="px-6 py-4"><button type="button" class="px-3 py-1 rounded-lg bg-[#2D6A4F]/10 text-[#2D6A4F] font-bold hover:bg-[#2D6A4F] hover:text-white transition-colors">Awaiting Hardware</button></td>
                </tr>
                <tr>
                    <td class="px-6 py-4 font-bold text-[#1B4332]">EC Conductivity Probe</td>
                    <td class="px-6 py-4 font-mono text-[#2D6A4F]">Pending hardware mapping</td>
                    <td class="px-6 py-4 font-mono">Pending hardware integration</td>
                    <td class="px-6 py-4 font-mono text-emerald-600">Pending hardware integration</td>
                    <td class="px-6 py-4 font-semibold text-[#2D6A4F]">Pending hardware integration</td>
                    <td class="px-6 py-4"><x-leaf.status-badge type="standby" label="Pending" /></td>
                    <td class="px-6 py-4"><button type="button" class="px-3 py-1 rounded-lg bg-[#2D6A4F]/10 text-[#2D6A4F] font-bold hover:bg-[#2D6A4F] hover:text-white transition-colors">Awaiting Hardware</button></td>
                </tr>
                <tr>
                    <td class="px-6 py-4 font-bold text-[#1B4332]">DS18B20 Water Temp</td>
                    <td class="px-6 py-4 font-mono text-[#2D6A4F]">Pending hardware mapping</td>
                    <td class="px-6 py-4 font-mono">Pending hardware mapping</td>
                    <td class="px-6 py-4 font-mono text-gray-500">Pending hardware integration</td>
                    <td class="px-6 py-4 font-semibold text-[#2D6A4F]">Pending hardware integration</td>
                    <td class="px-6 py-4"><x-leaf.status-badge type="standby" label="Pending" /></td>
                    <td class="px-6 py-4"><button type="button" class="px-3 py-1 rounded-lg bg-[#2D6A4F]/10 text-[#2D6A4F] font-bold hover:bg-[#2D6A4F] hover:text-white transition-colors">Awaiting Hardware</button></td>
                </tr>
            </x-leaf.table>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- TAB CONTENT 3: ANALYTICS HUB               -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'analytics'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8 min-w-0">
        
        <x-leaf.page-header 
            title="Historical Telemetry & Analytics" 
            subtitle="Deep historical insights into environmental variables, VPD trends, and nutrient balance."
            badge="Interactive Charts"
        />

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 lg:gap-6 min-w-0 items-start">
            <div class="xl:col-span-8 p-6 sm:p-8 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-4 min-w-0 overflow-hidden flex flex-col justify-between">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100 min-w-0">
                    <div class="min-w-0">
                        <h3 class="text-lg font-bold text-[#1B4332] truncate">Multi-Parameter Weekly Comparison</h3>
                        <p class="text-xs text-[#1B4332]/70 truncate">Air Temperature vs Atmospheric Humidity vs Water Flow</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" class="px-3 py-1 rounded-lg bg-[#2D6A4F] text-white text-xs font-bold shadow-sm">Waiting for history</button>
                        <button type="button" class="px-3 py-1 rounded-lg bg-gray-100 text-[#1B4332] text-xs font-bold hover:bg-gray-200">Export CSV</button>
                    </div>
                </div>

                <div wire:ignore id="analyticsMultiChart" class="w-full h-[300px] sm:h-[320px] lg:h-[360px] min-h-[300px] overflow-hidden relative">
                    <div x-show="!hasChartTelemetry" class="absolute inset-0 flex items-center justify-center text-sm font-medium text-[#1B4332]/70 bg-white/80 z-10 pointer-events-none">
                        Waiting for sensor data...
                    </div>
                </div>
            </div>

            <!-- Analytics Summary Breakdown -->
            <div class="xl:col-span-4 space-y-6 min-w-0">
                <div class="p-6 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-4 min-w-0">
                    <h3 class="text-base font-bold text-[#1B4332]">Vapor Pressure Deficit (VPD)</h3>
                    <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-100 space-y-2">
                        <div class="flex justify-between items-baseline min-w-0">
                            <span class="text-xs font-semibold text-[#1B4332]">Current VPD Index</span>
                            <span class="text-xl font-extrabold text-[#2D6A4F]">Waiting for telemetry</span>
                        </div>
                        <p class="text-xs text-[#1B4332]/80">VPD values will appear once sufficient telemetry is available from the connected device.</p>
                    </div>

                    <div class="space-y-3 pt-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500">Weekly pH Stability</span>
                            <span class="font-bold text-[#2D6A4F]">Waiting for telemetry</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500">EC Concentration Score</span>
                            <span class="font-bold text-[#2D6A4F]">Waiting for telemetry</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500">Total Water Consumed</span>
                            <span class="font-bold text-[#1B4332]">Waiting for telemetry</span>
                        </div>
                    </div>
                </div>

                <div class="p-6 rounded-3xl bg-[#1B43332] text-white space-y-3 shadow-lg min-w-0">
                    <span class="text-xs font-mono text-[#95D5B2] uppercase tracking-wider block">Telemetry Overview</span>
                    <h4 class="text-lg font-bold text-white">Waiting for historical telemetry</h4>
                    <p class="text-xs text-[#95D5B2]/90">Historical telemetry and trend summaries will appear once sufficient data is available.</p>
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- TAB CONTENT 4: DEVICE MANAGEMENT          -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'devices'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8 min-w-0">
        
        <x-leaf.page-header 
            title="ESP32 Hardware Devices & Relays" 
            subtitle="Manage microcontrollers, pinout mappings, actuator relay switches, and HTTP API endpoints."
            badge="{{ count($deviceCards) > 0 ? count($deviceCards).' Controllers Active' : 'Waiting for device...' }}"
        />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 min-w-0">
            @forelse ($deviceCards as $deviceCard)
                <div class="p-6 sm:p-8 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-6 min-w-0">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 min-w-0">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-12 h-12 rounded-2xl bg-[#2D6A4F]/10 text-[#2D6A4F] flex items-center justify-center text-xl font-bold shrink-0">
                                📟
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-lg font-bold text-[#1B4332] truncate">{{ $deviceCard['name'] }}</h3>
                                <p class="text-xs text-[#40916C] truncate">{{ $deviceCard['deviceId'] }}</p>
                            </div>
                        </div>
                        <x-leaf.status-badge :type="$deviceCard['isOnline'] ? 'online' : 'offline'" :label="$deviceCard['isOnline'] ? 'Online' : 'Offline'" class="shrink-0" />
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs min-w-0">
                        <div class="p-3 rounded-2xl bg-[#F8FAF8] border border-gray-100 min-w-0">
                            <span class="text-gray-500 block">Firmware</span>
                            <span class="font-mono font-bold text-[#1B4332] truncate">{{ $deviceCard['firmware'] }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-[#F8FAF8] border border-gray-100 min-w-0">
                            <span class="text-gray-500 block">Wi-Fi RSSI</span>
                            <span class="font-mono font-bold text-[#2D6A4F] truncate">{{ $deviceCard['wifiRssi'] }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-[#F8FAF8] border border-gray-100 min-w-0">
                            <span class="text-gray-500 block">Uptime</span>
                            <span class="font-mono font-bold text-[#2D6A4F] truncate">{{ $deviceCard['uptime'] }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-[#F8FAF8] border border-gray-100 min-w-0">
                            <span class="text-gray-500 block">Battery</span>
                            <span class="font-mono font-bold text-[#2D6A4F] truncate">{{ $deviceCard['battery'] }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-[#F8FAF8] border border-gray-100 min-w-0">
                            <span class="text-gray-500 block">HTTP API</span>
                            <span class="font-mono font-bold text-[#2D6A4F] truncate">{{ $deviceCard['transportStatus'] }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-[#F8FAF8] border border-gray-100 min-w-0">
                            <span class="text-gray-500 block">Last Seen</span>
                            <span class="font-mono font-bold text-[#1B4332] truncate">{{ $deviceCard['lastSeen'] }}</span>
                        </div>
                    </div>

                    <div class="pt-2 flex gap-3">
                        <button wire:click="queueDeviceCommand('restart_node')" type="button" class="flex-1 py-2.5 rounded-xl bg-[#2D6A4F] text-white text-xs font-bold hover:bg-[#1B4332] transition-colors">Restart Node</button>
                        <button wire:click="queueDeviceCommand('configure_pins')" type="button" class="flex-1 py-2.5 rounded-xl bg-[#2D6A4F]/10 text-[#2D6A4F] text-xs font-bold hover:bg-[#2D6A4F] hover:text-white transition-colors">Configure Pins</button>
                    </div>
                </div>
            @empty
                <div class="p-6 sm:p-8 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-6 min-w-0">
                    <div class="text-sm font-medium text-[#1B4332]/70">Waiting for device...</div>
                </div>
            @endforelse
        </div>

    </div>

    <!-- ========================================== -->
    <!-- TAB CONTENT 5: ALERTS & LOGS              -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'alerts'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8 min-w-0">
        
        <x-leaf.page-header 
            title="Automation Alerts & System Logs" 
            subtitle="Comprehensive log audit stream for environmental threshold triggers and HTTP API events."
            badge="Live Feed"
        />

        <div class="space-y-4 min-w-0">
            @forelse ($telemetryAlerts as $alert)
                <x-leaf.alert-card 
                    :title="$alert['title']"
                    :message="$alert['message']"
                    :time="$alert['time']"
                    :severity="$alert['card_severity']"
                    :read="$alert['acknowledged']"
                    wire:key="telemetry-alert-{{ $alert['key'] }}"
                    data-alert-key="{{ $alert['key'] }}"
                    data-alert-severity="{{ $alert['severity'] }}"
                    data-alert-timestamp="{{ $alert['timestamp'] }}"
                    data-alert-sensor="{{ $alert['sensor'] }}"
                    data-alert-icon="{{ $alert['icon'] }}"
                    data-alert-acknowledged="{{ $alert['acknowledged'] ? 'true' : 'false' }}"
                />
            @empty
                <x-leaf.alert-card 
                    title="Waiting for sensor data..."
                    message=""
                    time=""
                    severity="info"
                    :read="true"
                />
            @endforelse
        </div>

    </div>

    <!-- ========================================== -->
    <!-- TAB CONTENT 6: SYSTEM SETTINGS             -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'settings'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8 min-w-0">
        
        <x-leaf.page-header 
            title="System Configurations & Target Setpoints" 
            subtitle="Define target thresholds for hydroponic crop species, dosing intervals, and HTTP API connection settings."
            badge="Lettuce Mode"
        />

        <div class="p-6 sm:p-8 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-6 min-w-0">
            <h3 class="text-lg font-bold text-[#1B4332]">Target Environmental Setpoints</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 min-w-0">
                <div class="space-y-2 min-w-0">
                    <label class="block text-xs font-bold text-[#1B4332] uppercase">Target Water pH Min / Max</label>
                    <div class="flex gap-2">
                        <input type="text" value="{{ $phLowThreshold !== null ? $phLowThreshold : 'Not available' }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-[#1B4332]" readonly>
                        <input type="text" value="{{ $phHighThreshold !== null ? $phHighThreshold : 'Not available' }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-[#1B4332]" readonly>
                    </div>
                </div>

                <div class="space-y-2 min-w-0">
                    <label class="block text-xs font-bold text-[#1B4332] uppercase">Target EC Range (mS/cm)</label>
                    <div class="flex gap-2">
                        <input type="text" value="{{ $ecLowThreshold !== null ? $ecLowThreshold : 'Not available' }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-[#1B4332]" readonly>
                        <input type="text" value="{{ $ecHighThreshold !== null ? $ecHighThreshold : 'Not available' }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-[#1B4332]" readonly>
                    </div>
                </div>

                <div class="space-y-2 min-w-0">
                    <label class="block text-xs font-bold text-[#1B4332] uppercase">Air Temp Target (°C)</label>
                    <div class="flex gap-2">
                        <input type="text" value="{{ $temperatureLowThreshold !== null ? $temperatureLowThreshold : 'Not available' }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-[#1B4332]" readonly>
                        <input type="text" value="{{ $temperatureHighThreshold !== null ? $temperatureHighThreshold : 'Not available' }}" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-[#1B4332]" readonly>
                    </div>
                </div>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="button" class="px-6 py-3 rounded-xl bg-[#2D6A4F] text-white font-bold text-xs hover:bg-[#1B4332] transition-colors shadow-md">Save Setpoints</button>
            </div>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- TAB CONTENT 7: REPORTS & EXPORT            -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'reports'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8 min-w-0">
        
        <x-leaf.page-header 
            title="Telemetry Reports & Export" 
            subtitle="Export telemetry history and sensor data trends as CSV for research and compliance."
            badge="Telemetry"
        />

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 lg:gap-6 min-w-0 items-start">
            <div class="xl:col-span-12 p-6 sm:p-8 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-4 min-w-0 overflow-hidden flex flex-col justify-between">
                <h3 class="text-lg font-bold text-[#1B4332]">Telemetry History & Sensor Data Trends</h3>
                <div wire:ignore id="telemetryHistoryChart" class="w-full h-[260px] sm:h-[280px] lg:h-[320px] min-h-[260px] overflow-hidden relative">
                    <div x-show="!hasChartTelemetry" class="absolute inset-0 flex items-center justify-center text-sm font-medium text-[#1B4332]/70 bg-white/80 z-10 pointer-events-none">
                        Waiting for telemetry data...
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>