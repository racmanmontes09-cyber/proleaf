<div wire:poll.5s class="space-y-8 min-w-0" x-data="{
    initApexCharts() {
        this.$nextTick(() => {
            if (typeof ApexCharts !== 'undefined') {
                // 1. Dashboard 24h Telemetry Chart
                const chartEl = document.querySelector('#telemetryOverviewChart');
                if (chartEl && !chartEl.dataset.rendered) {
                    chartEl.dataset.rendered = 'true';
                    new ApexCharts(chartEl, {
                        series: [
                            { name: 'Water pH', data: [6.1, 6.2, 6.15, 6.3, 6.28, 6.3, 6.25, 6.3] },
                            { name: 'Water Temp (°C)', data: [21.8, 22.0, 22.1, 22.3, 22.4, 22.4, 22.2, 22.4] },
                            { name: 'Nutrient EC (mS)', data: [1.8, 1.85, 1.88, 1.9, 1.9, 1.89, 1.9, 1.9] }
                        ],
                        chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
                        colors: ['#2D6A4F', '#40916C', '#95D5B2'],
                        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] } },
                        dataLabels: { enabled: false },
                        stroke: { curve: 'smooth', width: 2.5 },
                        xaxis: { categories: ['00:00', '03:00', '06:00', '09:00', '12:00', '15:00', '18:00', 'NOW'], labels: { style: { colors: '#1B4332' } } },
                        yaxis: { labels: { style: { colors: '#1B4332' } } },
                        grid: { borderColor: '#E5E7EB', strokeDashArray: 4 },
                        legend: { position: 'top', horizontalAlign: 'right' }
                    }).render();
                }

                // 2. Analytics Multi-Axis Deep Dive Chart
                const analyticsEl = document.querySelector('#analyticsMultiChart');
                if (analyticsEl && !analyticsEl.dataset.rendered) {
                    analyticsEl.dataset.rendered = 'true';
                    new ApexCharts(analyticsEl, {
                        series: [
                            { name: 'Air Temp (°C)', type: 'column', data: [23.5, 24.1, 24.8, 25.2, 24.9, 24.8, 24.2] },
                            { name: 'Humidity (%)', type: 'line', data: [65, 67, 68, 70, 69, 68, 68] },
                            { name: 'Water Flow (L/min)', type: 'line', data: [2.3, 2.4, 2.4, 2.5, 2.4, 2.4, 2.4] }
                        ],
                        chart: { height: 320, type: 'line', toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
                        stroke: { width: [0, 3, 3], curve: 'smooth' },
                        colors: ['#D8F3DC', '#2D6A4F', '#40916C'],
                        plotOptions: { bar: { columnWidth: '40%', borderRadius: 6 } },
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        xaxis: { type: 'category' },
                        grid: { borderColor: '#F1F5F9' }
                    }).render();
                }

                // 3. Reports Yield Distribution Chart
                const yieldEl = document.querySelector('#yieldDistributionChart');
                if (yieldEl && !yieldEl.dataset.rendered) {
                    yieldEl.dataset.rendered = 'true';
                    new ApexCharts(yieldEl, {
                        series: [44, 55, 13, 33],
                        chart: { type: 'donut', height: 260, fontFamily: 'Inter, sans-serif' },
                        labels: ['Grade A Butterhead', 'Grade A Romaine', 'Grade B Green Leaf', 'Sample Testing'],
                        colors: ['#2D6A4F', '#40916C', '#74C69D', '#B7E4C7'],
                        legend: { position: 'bottom' }
                    }).render();
                }
            }
        });
    }
}" x-init="initApexCharts()" x-effect="initApexCharts()">

    <!-- ========================================== -->
    <!-- TOP GLOBAL SYSTEM BANNER                  -->
    <!-- ========================================== -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-[#1B4332] via-[#2D6A4F] to-[#1B4332] text-white shadow-xl relative overflow-hidden min-w-0">
        <!-- Ambient Background Glows -->
        <div class="absolute -top-12 -right-12 w-64 h-64 bg-[#95D5B2]/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="space-y-2 relative z-10 min-w-0">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-[#95D5B2] text-xs font-semibold border border-white/15 backdrop-blur-md">
                <span class="w-2 h-2 rounded-full bg-[#95D5B2] animate-pulse shrink-0"></span>
                <span class="truncate">{{ $bannerStatusLabel }}</span>
                <span class="text-white/40 shrink-0">•</span>
                <span class="truncate">MQTT Broker: Connected</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight truncate">
                Project L.E.A.F. Control Hub
            </h1>
            <p class="text-xs sm:text-sm text-[#95D5B2]/90 font-medium truncate">
                Automated Hydroponic Lettuce Environment (Lactuca sativa) • Live Telemetry Stream
            </p>
        </div>

        <!-- Quick Status Metrics Pill Badge Group -->
        <div class="relative z-10 flex flex-wrap items-center gap-3 shrink-0">
            <div class="px-4 py-2.5 rounded-2xl bg-white/10 border border-white/20 backdrop-blur-md flex items-center gap-3">
                <span class="relative flex h-3 w-3 shrink-0">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-400"></span>
                </span>
                <div>
                    <span class="block text-xs font-bold text-white uppercase tracking-wider">{{ $deviceNameLabel }}</span>
                    <span class="text-[10px] text-[#95D5B2] font-mono">{{ $lastUpdatedLabel }}</span>
                </div>
            </div>

            <div class="px-4 py-2.5 rounded-2xl bg-white/10 border border-white/20 backdrop-blur-md flex items-center gap-3">
                <svg class="w-5 h-5 text-[#95D5B2] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <div>
                    <span class="block text-xs font-bold text-white uppercase tracking-wider">Battery</span>
                    <span class="text-[10px] text-[#95D5B2] font-mono">95% (Solar Backed)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB CONTENT 1: DASHBOARD OVERVIEW           -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'dashboard'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8 min-w-0">
        
        <!-- KPI METRICS GRID -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7 gap-4 sm:gap-5 min-w-0 items-stretch">
            
            <!-- Air Temp -->
            <x-leaf.kpi-card 
                title="Air Temp" 
                value="{{ $airTempValue }}" 
                unit="°C" 
                status="Optimal" 
                statusType="online" 
                target="20-25°C"
                trend="✓ Within Target"
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
                value="{{ $airHumidityValue }}" 
                unit="%" 
                status="Good VPD" 
                statusType="online" 
                target="60-75%"
                trend="✓ Optimal RH"
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
                value="{{ $waterTempValue }}" 
                unit="°C" 
                status="Optimal" 
                statusType="online" 
                target="18-23°C"
                trend="✓ Cool Solution"
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
                value="{{ $waterPhValue }}" 
                unit="pH" 
                status="Balanced" 
                statusType="online" 
                target="5.8-6.5"
                trend="✓ Ideal Uptake"
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
                value="{{ $nutrientEcValue }}" 
                unit="mS/cm" 
                status="Optimal" 
                statusType="online" 
                target="1.5-2.0"
                trend="✓ Standard Ratio"
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
                status="Tank High" 
                statusType="online" 
                target="> 50%"
                trend="✓ Tank A Ready"
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
                status="Running" 
                statusType="online" 
                target="2.0-3.0"
                trend="✓ Pump Active"
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
                        Live MQTT Feed
                    </span>
                </div>

                <div id="telemetryOverviewChart" class="w-full h-[280px] sm:h-[320px] lg:h-[340px] min-h-[280px] overflow-hidden"></div>
            </div>

            <!-- Right Column: Node Specs & Relays (4 Cols) -->
            <div class="xl:col-span-4 space-y-6 min-w-0">
                
                <!-- Controller Card -->
                <div class="p-6 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-4 min-w-0">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 min-w-0">
                        <h3 class="text-base font-bold text-[#1B4332] truncate">ESP32 Controller Node</h3>
                        <x-leaf.status-badge :type="$deviceStatusType" :label="$deviceStatusLabel" class="shrink-0" />
                    </div>

                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between py-1.5 border-b border-gray-50 min-w-0">
                            <span class="text-gray-500">Device Name</span>
                            <span class="font-mono font-bold text-[#2D6A4F] truncate">{{ $deviceNameLabel }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-gray-50 min-w-0">
                            <span class="text-gray-500">Last Seen</span>
                            <span class="font-mono text-gray-700 truncate">{{ $lastSeenLabel }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-gray-50 min-w-0">
                            <span class="text-gray-500">Firmware Version</span>
                            <span class="font-mono text-gray-700 truncate">{{ $firmwareLabel }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-gray-50 min-w-0">
                            <span class="text-gray-500">Local IP Address</span>
                            <span class="font-mono text-[#2D6A4F] font-semibold truncate">{{ $localIpLabel }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-gray-50 min-w-0">
                            <span class="text-gray-500">Wi-Fi RSSI</span>
                            <span class="font-mono text-[#2D6A4F] font-semibold truncate">{{ $wifiRssiLabel }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 min-w-0">
                            <span class="text-gray-500">Uptime</span>
                            <span class="font-mono text-gray-700 truncate">{{ $systemUptimeLabel }}</span>
                        </div>
                    </div>
                </div>

                <!-- Relay Actuators -->
                <div class="p-6 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-4 min-w-0">
                    <h3 class="text-base font-bold text-[#1B4332]">Actuator Relays</h3>
                    <div class="space-y-3 min-w-0">
                        <div class="p-3.5 rounded-2xl bg-[#F8FAF8] border border-[#2D6A4F]/10 flex items-center justify-between min-w-0">
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-[#1B4332] truncate">Water Circulation Pump</p>
                                <p class="text-[10px] text-gray-500 truncate">Flow Rate: 2.4 L/min</p>
                            </div>
                            <span class="px-2.5 py-1 text-[10px] font-bold bg-[#2D6A4F] text-white rounded-lg shrink-0">RUNNING</span>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-[#F8FAF8] border border-[#2D6A4F]/10 flex items-center justify-between min-w-0">
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-[#1B4332] truncate">Nutrient Dosing Pump A</p>
                                <p class="text-[10px] text-gray-500 truncate">Scheduled Cycle: 15m</p>
                            </div>
                            <span class="px-2.5 py-1 text-[10px] font-bold bg-[#95D5B2]/40 text-[#1B4332] rounded-lg shrink-0">AUTO</span>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-[#F8FAF8] border border-[#2D6A4F]/10 flex items-center justify-between min-w-0">
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-[#1B4332] truncate">VPD Intake Cooling Fan</p>
                                <p class="text-[10px] text-gray-500 truncate">Trigger: >24.5°C Air</p>
                            </div>
                            <span class="px-2.5 py-1 text-[10px] font-bold bg-amber-100 text-amber-800 rounded-lg shrink-0">ACTIVE</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- RECENT ACTIVITY TIMELINE & QUICK ACTIONS -->
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 lg:gap-6 min-w-0 items-start">
            
            <!-- Timeline Log (8 Cols) -->
            <div class="xl:col-span-8 p-6 sm:p-8 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-4 min-w-0">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 min-w-0">
                    <h3 class="text-base font-bold text-[#1B4332] truncate">System Events Log</h3>
                    <span class="text-xs text-[#2D6A4F] font-semibold shrink-0">Real-Time Telemetry Stream</span>
                </div>

                <div class="space-y-3 min-w-0">
                    <x-leaf.alert-card 
                        title="Water pH Balanced" 
                        message="pH reading locked at 6.3 pH following 10ml micro-dosing pulse." 
                        time="2 mins ago" 
                        severity="success" 
                        read="false"
                    />

                    <x-leaf.alert-card 
                        title="VPD Cooling Triggered" 
                        message="Air temperature reached 24.8°C. Intake fan speed adjusted to 60% PWM." 
                        time="18 mins ago" 
                        severity="info" 
                        read="true"
                    />

                    <x-leaf.alert-card 
                        title="ESP32 MQTT Ping OK" 
                        message="Telemetry packet acknowledge roundtrip latency: 24ms." 
                        time="1 hour ago" 
                        severity="info" 
                        read="true"
                    />
                </div>
            </div>

            <!-- Quick Action Grid (4 Cols) -->
            <div class="xl:col-span-4 p-6 sm:p-8 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-4 min-w-0">
                <h3 class="text-base font-bold text-[#1B4332]">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-3 min-w-0">
                    <button @click="activeTab = 'monitoring'" type="button" class="p-4 rounded-2xl bg-[#F8FAF8] border border-[#2D6A4F]/15 hover:bg-[#2D6A4F] hover:text-white transition-all text-left text-xs font-bold text-[#1B4332] space-y-1 group min-w-0">
                        <div class="text-lg">📡</div>
                        <div class="truncate">View Gauges</div>
                    </button>
                    <button @click="activeTab = 'analytics'" type="button" class="p-4 rounded-2xl bg-[#F8FAF8] border border-[#2D6A4F]/15 hover:bg-[#2D6A4F] hover:text-white transition-all text-left text-xs font-bold text-[#1B4332] space-y-1 group min-w-0">
                        <div class="text-lg">📈</div>
                        <div class="truncate">Full Charts</div>
                    </button>
                    <button @click="activeTab = 'devices'" type="button" class="p-4 rounded-2xl bg-[#F8FAF8] border border-[#2D6A4F]/15 hover:bg-[#2D6A4F] hover:text-white transition-all text-left text-xs font-bold text-[#1B4332] space-y-1 group min-w-0">
                        <div class="text-lg">⚡</div>
                        <div class="truncate">Node Control</div>
                    </button>
                    <button @click="activeTab = 'reports'" type="button" class="p-4 rounded-2xl bg-[#F8FAF8] border border-[#2D6A4F]/15 hover:bg-[#2D6A4F] hover:text-white transition-all text-left text-xs font-bold text-[#1B4332] space-y-1 group min-w-0">
                        <div class="text-lg">📋</div>
                        <div class="truncate">Export PDF</div>
                    </button>
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
            badge="9 Active Sensors"
        />

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 min-w-0">
            <x-leaf.sensor-card 
                name="Ambient Air Temperature" 
                value="24.8" 
                unit="°C" 
                status="Optimal" 
                statusType="online" 
                min="10" 
                max="40" 
                percentage="62" 
                optimalRange="20.0 - 25.0°C" 
                lastCalibrated="1 day ago" 
            />

            <x-leaf.sensor-card 
                name="Relative Air Humidity" 
                value="68" 
                unit="%" 
                status="Balanced" 
                statusType="online" 
                min="30" 
                max="95" 
                percentage="68" 
                optimalRange="60.0 - 75.0%" 
                lastCalibrated="3 days ago" 
            />

            <x-leaf.sensor-card 
                name="Water Solution Temp" 
                value="22.4" 
                unit="°C" 
                status="Cool" 
                statusType="online" 
                min="15" 
                max="30" 
                percentage="50" 
                optimalRange="18.0 - 23.0°C" 
                lastCalibrated="Today" 
            />

            <x-leaf.sensor-card 
                name="Hydroponic Solution pH" 
                value="6.3" 
                unit="pH" 
                status="Ideal" 
                statusType="online" 
                min="4.0" 
                max="9.0" 
                percentage="63" 
                optimalRange="5.80 - 6.50" 
                lastCalibrated="Yesterday" 
            />

            <x-leaf.sensor-card 
                name="Electrical Conductivity (EC)" 
                value="1.9" 
                unit="mS/cm" 
                status="Optimal" 
                statusType="online" 
                min="0.5" 
                max="3.0" 
                percentage="70" 
                optimalRange="1.50 - 2.00" 
                lastCalibrated="Yesterday" 
            />

            <x-leaf.sensor-card 
                name="Reservoir Water Level" 
                value="84" 
                unit="%" 
                status="High" 
                statusType="online" 
                min="0" 
                max="100" 
                percentage="84" 
                optimalRange="> 50%" 
                lastCalibrated="Auto Float" 
            />

            <x-leaf.sensor-card 
                name="Water Flow Rate" 
                value="2.4" 
                unit="L/min" 
                status="Normal" 
                statusType="online" 
                min="0" 
                max="5.0" 
                percentage="48" 
                optimalRange="2.0 - 3.0 L/m" 
                lastCalibrated="Hall Sensor OK" 
            />

            <x-leaf.sensor-card 
                name="PAR Light Intensity" 
                value="420" 
                unit="µmol/m²/s" 
                status="Good" 
                statusType="online" 
                min="0" 
                max="800" 
                percentage="53" 
                optimalRange="350 - 500" 
                lastCalibrated="5 days ago" 
            />

            <x-leaf.sensor-card 
                name="Ambient CO2 Level" 
                value="780" 
                unit="PPM" 
                status="Enriched" 
                statusType="online" 
                min="300" 
                max="1500" 
                percentage="52" 
                optimalRange="600 - 900" 
                lastCalibrated="1 week ago" 
            />
        </div>

        <!-- Sensor Diagnostics Table -->
        <div class="space-y-4 min-w-0">
            <h3 class="text-lg font-bold text-[#1B4332]">Sensor Hardware Calibration & ADC Signals</h3>
            <x-leaf.table :headers="['Sensor Name', 'Hardware Pin', 'Raw ADC Voltage', 'Offset Drift', 'Signal Quality', 'Status', 'Actions']">
                <tr>
                    <td class="px-6 py-4 font-bold text-[#1B4332]">Analog pH Probe</td>
                    <td class="px-6 py-4 font-mono text-[#2D6A4F]">GPIO34 (ADC1_CH6)</td>
                    <td class="px-6 py-4 font-mono">1.842 V</td>
                    <td class="px-6 py-4 font-mono text-emerald-600">+0.02 pH</td>
                    <td class="px-6 py-4 font-semibold text-[#2D6A4F]">99.4% (Clean)</td>
                    <td class="px-6 py-4"><x-leaf.status-badge type="online" label="Calibrated" /></td>
                    <td class="px-6 py-4"><button type="button" class="px-3 py-1 rounded-lg bg-[#2D6A4F]/10 text-[#2D6A4F] font-bold hover:bg-[#2D6A4F] hover:text-white transition-colors">Recalibrate</button></td>
                </tr>
                <tr>
                    <td class="px-6 py-4 font-bold text-[#1B4332]">EC Conductivity Probe</td>
                    <td class="px-6 py-4 font-mono text-[#2D6A4F]">GPIO35 (ADC1_CH7)</td>
                    <td class="px-6 py-4 font-mono">1.215 V</td>
                    <td class="px-6 py-4 font-mono text-emerald-600">-0.01 mS</td>
                    <td class="px-6 py-4 font-semibold text-[#2D6A4F]">98.9% (Clean)</td>
                    <td class="px-6 py-4"><x-leaf.status-badge type="online" label="Calibrated" /></td>
                    <td class="px-6 py-4"><button type="button" class="px-3 py-1 rounded-lg bg-[#2D6A4F]/10 text-[#2D6A4F] font-bold hover:bg-[#2D6A4F] hover:text-white transition-colors">Recalibrate</button></td>
                </tr>
                <tr>
                    <td class="px-6 py-4 font-bold text-[#1B4332]">DS18B20 Water Temp</td>
                    <td class="px-6 py-4 font-mono text-[#2D6A4F]">GPIO4 (OneWire)</td>
                    <td class="px-6 py-4 font-mono">Digital Bus</td>
                    <td class="px-6 py-4 font-mono text-gray-500">0.00 °C</td>
                    <td class="px-6 py-4 font-semibold text-[#2D6A4F]">100% (CRC OK)</td>
                    <td class="px-6 py-4"><x-leaf.status-badge type="online" label="Calibrated" /></td>
                    <td class="px-6 py-4"><button type="button" class="px-3 py-1 rounded-lg bg-[#2D6A4F]/10 text-[#2D6A4F] font-bold hover:bg-[#2D6A4F] hover:text-white transition-colors">Recalibrate</button></td>
                </tr>
            </x-leaf.table>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- TAB CONTENT 3: ANALYTICS HUB               -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'analytics'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8 min-w-0">
        
        <x-leaf.page-header 
            title="Historical Telemetry & Crop Analytics" 
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
                        <button type="button" class="px-3 py-1 rounded-lg bg-[#2D6A4F] text-white text-xs font-bold shadow-sm">Week 29</button>
                        <button type="button" class="px-3 py-1 rounded-lg bg-gray-100 text-[#1B4332] text-xs font-bold hover:bg-gray-200">Export CSV</button>
                    </div>
                </div>

                <div id="analyticsMultiChart" class="w-full h-[300px] sm:h-[320px] lg:h-[360px] min-h-[300px] overflow-hidden"></div>
            </div>

            <!-- Analytics Summary Breakdown -->
            <div class="xl:col-span-4 space-y-6 min-w-0">
                <div class="p-6 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-4 min-w-0">
                    <h3 class="text-base font-bold text-[#1B4332]">Vapor Pressure Deficit (VPD)</h3>
                    <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-100 space-y-2">
                        <div class="flex justify-between items-baseline min-w-0">
                            <span class="text-xs font-semibold text-[#1B4332]">Current VPD Index</span>
                            <span class="text-xl font-extrabold text-[#2D6A4F]">0.94 kPa</span>
                        </div>
                        <p class="text-xs text-[#1B4332]/80">Target for Vegetative Lettuce: 0.8 - 1.1 kPa. Transpiration efficiency is running at optimal levels.</p>
                    </div>

                    <div class="space-y-3 pt-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500">Weekly pH Stability</span>
                            <span class="font-bold text-[#2D6A4F]">98.2%</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500">EC Concentration Score</span>
                            <span class="font-bold text-[#2D6A4F]">96.7%</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-500">Total Water Consumed</span>
                            <span class="font-bold text-[#1B4332]">84.6 Liters</span>
                        </div>
                    </div>
                </div>

                <div class="p-6 rounded-3xl bg-[#1B4332] text-white space-y-3 shadow-lg min-w-0">
                    <span class="text-xs font-mono text-[#95D5B2] uppercase tracking-wider block">🌾 CROP GROWTH PHASE</span>
                    <h4 class="text-lg font-bold text-white">Butterhead Lettuce (Day 18)</h4>
                    <p class="text-xs text-[#95D5B2]/90">Estimated harvest date in 14 days. Projected yield: 18.4 kg / NFT channel.</p>
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
            subtitle="Manage microcontrollers, pinout mappings, actuator relay switches, and MQTT topics."
            badge="2 Controllers Active"
        />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 min-w-0">
            
            <!-- Device 1: Primary Controller -->
            <div class="p-6 sm:p-8 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-6 min-w-0">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 min-w-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-12 h-12 rounded-2xl bg-[#2D6A4F]/10 text-[#2D6A4F] flex items-center justify-center text-xl font-bold shrink-0">
                            📟
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-bold text-[#1B4332] truncate">Node LEAF-ESP32-01</h3>
                            <p class="text-xs text-[#40916C] truncate">Primary NFT Channel Controller</p>
                        </div>
                    </div>
                    <x-leaf.status-badge type="online" label="Online" class="shrink-0" />
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs min-w-0">
                    <div class="p-3 rounded-2xl bg-[#F8FAF8] border border-gray-100 min-w-0">
                        <span class="text-gray-500 block">IP Address</span>
                        <span class="font-mono font-bold text-[#1B4332] truncate">192.168.1.145</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-[#F8FAF8] border border-gray-100 min-w-0">
                        <span class="text-gray-500 block">MAC Address</span>
                        <span class="font-mono font-bold text-[#1B4332] truncate">24:6F:28:AB:7C:12</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-[#F8FAF8] border border-gray-100 min-w-0">
                        <span class="text-gray-500 block">MQTT Topic</span>
                        <span class="font-mono font-bold text-[#2D6A4F] truncate">leaf/node1/telemetry</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-[#F8FAF8] border border-gray-100 min-w-0">
                        <span class="text-gray-500 block">Wi-Fi RSSI</span>
                        <span class="font-mono font-bold text-[#2D6A4F] truncate">-62 dBm</span>
                    </div>
                </div>

                <div class="pt-2 flex gap-3">
                    <button type="button" class="flex-1 py-2.5 rounded-xl bg-[#2D6A4F] text-white text-xs font-bold hover:bg-[#1B4332] transition-colors">Restart Node</button>
                    <button type="button" class="flex-1 py-2.5 rounded-xl bg-[#2D6A4F]/10 text-[#2D6A4F] text-xs font-bold hover:bg-[#2D6A4F] hover:text-white transition-colors">Configure Pins</button>
                </div>
            </div>

            <!-- Device 2: Secondary Dosing Gateway -->
            <div class="p-6 sm:p-8 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-6 min-w-0">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 min-w-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-12 h-12 rounded-2xl bg-[#2D6A4F]/10 text-[#2D6A4F] flex items-center justify-center text-xl font-bold shrink-0">
                            ⚙️
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-lg font-bold text-[#1B4332] truncate">Node LEAF-ESP32-02</h3>
                            <p class="text-xs text-[#40916C] truncate">Automated Dosing Pump Gateway</p>
                        </div>
                    </div>
                    <x-leaf.status-badge type="online" label="Online" class="shrink-0" />
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs min-w-0">
                    <div class="p-3 rounded-2xl bg-[#F8FAF8] border border-gray-100 min-w-0">
                        <span class="text-gray-500 block">IP Address</span>
                        <span class="font-mono font-bold text-[#1B4332] truncate">192.168.1.146</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-[#F8FAF8] border border-gray-100 min-w-0">
                        <span class="text-gray-500 block">MAC Address</span>
                        <span class="font-mono font-bold text-[#1B4332] truncate">24:6F:28:AB:7C:19</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-[#F8FAF8] border border-gray-100 min-w-0">
                        <span class="text-gray-500 block">MQTT Topic</span>
                        <span class="font-mono font-bold text-[#2D6A4F] truncate">leaf/dosing/command</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-[#F8FAF8] border border-gray-100 min-w-0">
                        <span class="text-gray-500 block">Wi-Fi RSSI</span>
                        <span class="font-mono font-bold text-[#2D6A4F] truncate">-58 dBm</span>
                    </div>
                </div>

                <div class="pt-2 flex gap-3">
                    <button type="button" class="flex-1 py-2.5 rounded-xl bg-[#2D6A4F] text-white text-xs font-bold hover:bg-[#1B4332] transition-colors">Restart Node</button>
                    <button type="button" class="flex-1 py-2.5 rounded-xl bg-[#2D6A4F]/10 text-[#2D6A4F] text-xs font-bold hover:bg-[#2D6A4F] hover:text-white transition-colors">Configure Pins</button>
                </div>
            </div>

        </div>

    </div>

    <!-- ========================================== -->
    <!-- TAB CONTENT 5: ALERTS & LOGS              -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'alerts'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8 min-w-0">
        
        <x-leaf.page-header 
            title="Automation Alerts & System Logs" 
            subtitle="Comprehensive log audit stream for environmental threshold triggers and MQTT events."
            badge="Live Feed"
        />

        <div class="space-y-4 min-w-0">
            <x-leaf.alert-card 
                title="Water pH Advisory" 
                message="Solution pH recorded at 6.3 (Target: 5.8 - 6.5). Automated pH-down dosing queued for next cycle." 
                time="5 mins ago" 
                severity="info" 
                read="false"
            />

            <x-leaf.alert-card 
                title="Air Temperature Warning Trigger" 
                message="Ambient air temp spiked to 24.8°C. VPD cooling intake fan automatically engaged." 
                time="22 mins ago" 
                severity="warning" 
                read="false"
            />

            <x-leaf.alert-card 
                title="Reservoir Tank Water Level Check" 
                message="Tank level is currently 84%. Sufficient water capacity for next 72 hours." 
                time="1 hour ago" 
                severity="success" 
                read="true"
            />

            <x-leaf.alert-card 
                title="ESP32 Telemetry Ping Acknowledged" 
                message="MQTT heartbeat sync successful. 0 dropped packets." 
                time="3 hours ago" 
                severity="info" 
                read="true"
            />
        </div>

    </div>

    <!-- ========================================== -->
    <!-- TAB CONTENT 6: SYSTEM SETTINGS             -->
    <!-- ========================================== -->
    <div x-show="activeTab === 'settings'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8 min-w-0">
        
        <x-leaf.page-header 
            title="System Configurations & Target Setpoints" 
            subtitle="Define target thresholds for hydroponic crop species, dosing intervals, and MQTT connection settings."
            badge="Lettuce Mode"
        />

        <div class="p-6 sm:p-8 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-6 min-w-0">
            <h3 class="text-lg font-bold text-[#1B4332]">Target Environmental Setpoints</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 min-w-0">
                <div class="space-y-2 min-w-0">
                    <label class="block text-xs font-bold text-[#1B4332] uppercase">Target Water pH Min / Max</label>
                    <div class="flex gap-2">
                        <input type="text" value="5.80" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-[#1B4332]">
                        <input type="text" value="6.50" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-[#1B4332]">
                    </div>
                </div>

                <div class="space-y-2 min-w-0">
                    <label class="block text-xs font-bold text-[#1B4332] uppercase">Target EC Range (mS/cm)</label>
                    <div class="flex gap-2">
                        <input type="text" value="1.50" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-[#1B4332]">
                        <input type="text" value="2.00" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-[#1B4332]">
                    </div>
                </div>

                <div class="space-y-2 min-w-0">
                    <label class="block text-xs font-bold text-[#1B4332] uppercase">Air Temp Target (°C)</label>
                    <div class="flex gap-2">
                        <input type="text" value="20.0" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-[#1B4332]">
                        <input type="text" value="25.0" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-[#1B4332]">
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
            title="Crop Yield Reports & Data Export" 
            subtitle="Generate commercial farm productivity reports, nutrient usage audits, and raw CSV data logs."
            badge="Export Ready"
        />

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 lg:gap-6 min-w-0 items-start">
            <div class="xl:col-span-8 p-6 sm:p-8 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-4 min-w-0 overflow-hidden flex flex-col justify-between">
                <h3 class="text-lg font-bold text-[#1B4332]">Yield Distribution by Lettuce Variety</h3>
                <div id="yieldDistributionChart" class="w-full h-[260px] sm:h-[280px] lg:h-[320px] min-h-[260px] overflow-hidden"></div>
            </div>

            <div class="xl:col-span-4 p-6 sm:p-8 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-6 min-w-0">
                <h3 class="text-base font-bold text-[#1B4332]">Export Telemetry Logs</h3>
                <p class="text-xs text-gray-600">Download formatted CSV or PDF reports containing 1-minute telemetry resolution for research and compliance audit.</p>

                <div class="space-y-3 min-w-0">
                    <button type="button" class="w-full py-3 rounded-xl bg-[#2D6A4F] text-white text-xs font-bold hover:bg-[#1B4332] transition-colors shadow-sm flex items-center justify-center gap-2">
                        📄 Download Monthly PDF Report
                    </button>
                    <button type="button" class="w-full py-3 rounded-xl bg-[#2D6A4F]/10 text-[#2D6A4F] text-xs font-bold hover:bg-[#2D6A4F] hover:text-white transition-colors flex items-center justify-center gap-2">
                        📊 Export Raw Telemetry CSV
                    </button>
                </div>
            </div>
        </div>

    </div>

</div>