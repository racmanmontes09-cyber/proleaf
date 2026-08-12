import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    forceTLS: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
        },
    },
});

if (window.Echo?.connector?.pusher?.connection) {
    const pusherConnection = window.Echo.connector.pusher.connection;
    pusherConnection.bind('connected', () => {
        console.log('Echo connected to Pusher:', pusherConnection.state);
    });
    pusherConnection.bind('error', (error) => {
        console.error('Echo Pusher connection error:', error);
    });
}



window.leafDashboardCharts = function leafDashboardCharts(config = {}) {
    const kpiKeys = [
        'air_temperature',
        'humidity',
        'water_temperature',
        'ph',
        'ec',
        'water_level',
        'water_flow',
    ];

    const emptyKpi = () => ({
        value: '--',
        status: 'Waiting',
        statusType: 'standby',
        trend: 'Waiting for sensor data...',
    });

    const normalizeKpiState = (kpis = {}) => Object.fromEntries(kpiKeys.map((key) => {
        const kpi = kpis?.[key] || {};

        return [key, {
            value: kpi.value ?? '--',
            status: kpi.status ?? 'Waiting',
            statusType: kpi.statusType ?? 'standby',
            trend: kpi.trend ?? 'Waiting for sensor data...',
        }];
    }));

    const emptyPulseState = () => Object.fromEntries(kpiKeys.map((key) => [key, false]));

    return {
        activeTab: config.activeTab || 'dashboard',
        hasChartTelemetry: Boolean(config.hasChartTelemetry),
        initialized: false,
        subscribed: false,
        polling: false,
        pollTimer: null,
        useEchoTelemetry: Boolean(config.useEchoTelemetry),
        deviceId: config.deviceId || null,
        pollingUrl: config.pollingUrl || '/dashboard/telemetry/readings',
        maxPoints: Number.parseInt(config.maxPoints || 60, 10),
        pollIntervalMs: Number.parseInt(config.pollIntervalMs || 1000, 10),
        pollBatchLimit: Number.parseInt(config.pollBatchLimit || 120, 10),
        lastReadingId: 0,
        kpiKeys,
        kpis: normalizeKpiState(config.initialKpis || config.initialChartPayload?.telemetryKpis),
        kpiPulse: emptyPulseState(),
        kpiPulseTimers: {},
        charts: {
            telemetryOverview: null,
            analytics: null,
            history: null,
        },
        overviewData: {
            ph: [],
            waterTemp: [],
            ec: [],
        },
        analyticsData: {
            airTemp: [],
            humidity: [],
            waterFlow: [],
        },
        historyData: {
            airTemp: [],
            humidity: [],
            waterTemp: [],
            ph: [],
            ec: [],
            waterFlow: [],
            waterLevel: [],
        },
        initialChartPayload() {
            return config.initialChartPayload || {};
        },
        initApexCharts() {
            this.$nextTick(() => {
                this.renderOrUpdateCharts(this.initialChartPayload());
                this.listenToPusher();
                this.startTelemetryPolling();
            });
        },
        listenToPusher() {
            if (!this.useEchoTelemetry || typeof window.Echo === 'undefined' || this.subscribed || !this.deviceId) {
                return;
            }

            this.subscribed = true;
            const channel = window.Echo.private(`devices.${this.deviceId}.telemetry`);
            const handler = (data) => this.handleTelemetryReceived(data);
            channel.listen('.TelemetryReceived', handler);
            channel.listen('TelemetryReceived', handler);
        },
        startTelemetryPolling() {
            if (this.pollTimer) {
                return;
            }

            this.scheduleNextTelemetryPoll(0);
        },
        stopTelemetryPolling() {
            if (this.pollTimer) {
                window.clearTimeout(this.pollTimer);
                this.pollTimer = null;
            }
        },
        scheduleNextTelemetryPoll(delay = this.pollIntervalMs) {
            this.stopTelemetryPolling();
            this.pollTimer = window.setTimeout(() => this.pollTelemetryReadings(), delay);
        },
        async pollTelemetryReadings() {
            if (this.polling) {
                this.scheduleNextTelemetryPoll();
                return;
            }

            this.polling = true;
            const requestedDeviceId = this.normalizeDeviceId(this.deviceId);

            try {
                const url = new URL(this.pollingUrl, window.location.origin);
                if (requestedDeviceId) {
                    url.searchParams.set('device_id', requestedDeviceId);
                }
                url.searchParams.set('after_id', String(this.lastReadingId || 0));
                url.searchParams.set('limit', String(this.pollBatchLimit));

                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });

                if (!response.ok) {
                    throw new Error(`Telemetry polling failed with status ${response.status}`);
                }

                const json = await response.json();
                if (requestedDeviceId && this.normalizeDeviceId(this.deviceId) !== requestedDeviceId) {
                    return;
                }

                this.applyResponseDeviceId(json.device_id);

                const readings = Array.isArray(json.readings) ? json.readings : [];

                if (readings.length > 0) {
                    this.handleTelemetryReadings(readings, json.latest_kpis || null);
                }
            } catch (error) {
                console.debug('Telemetry chart polling error:', error);
            } finally {
                this.polling = false;
                this.scheduleNextTelemetryPoll();
            }
        },
        normalizeDeviceId(deviceId) {
            const parsed = Number.parseInt(deviceId || 0, 10);
            return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
        },
        applyResponseDeviceId(deviceId) {
            const nextDeviceId = this.normalizeDeviceId(deviceId);
            if (!nextDeviceId) {
                return;
            }

            const currentDeviceId = this.normalizeDeviceId(this.deviceId);
            if (!currentDeviceId) {
                this.deviceId = nextDeviceId;
                this.listenToPusher();
                return;
            }

            if (currentDeviceId !== nextDeviceId) {
                this.switchTelemetryDevice(nextDeviceId, false);
            }
        },
        switchTelemetryDevice(deviceId, restartPoll = true) {
            const nextDeviceId = this.normalizeDeviceId(deviceId);
            const currentDeviceId = this.normalizeDeviceId(this.deviceId);

            if (nextDeviceId === currentDeviceId) {
                return;
            }

            this.deviceId = nextDeviceId;
            this.subscribed = false;
            this.hasChartTelemetry = false;
            this.resetSeries();
            this.resetKpis();
            this.refreshMountedCharts(false);
            this.listenToPusher();

            if (restartPoll) {
                this.scheduleNextTelemetryPoll(0);
            }
        },
        handleTelemetryReceived(data) {
            if (!data) {
                return;
            }

            this.handleTelemetryReadings([data]);
        },
        handleTelemetryReadings(readings, latestKpis = null) {
            const normalized = readings
                .map((reading) => this.normalizeReading(reading))
                .filter((reading) => reading !== null && this.readingBelongsToCurrentDevice(reading))
                .sort((a, b) => a.id - b.id);

            if (normalized.length === 0) {
                return;
            }

            let changed = false;
            let latestAcceptedReading = null;

            normalized.forEach((reading) => {
                if (reading.id > 0 && reading.id <= this.lastReadingId) {
                    return;
                }

                this.pushReading(reading);
                this.lastReadingId = Math.max(this.lastReadingId, reading.id || 0);
                latestAcceptedReading = reading;
                changed = true;
            });

            if (changed) {
                this.hasChartTelemetry = true;
                this.updateKpis(latestKpis || this.buildKpisFromReading(latestAcceptedReading));
                this.trimSeries();
                this.refreshMountedCharts();
            }
        },
        readingBelongsToCurrentDevice(reading) {
            const currentDeviceId = this.normalizeDeviceId(this.deviceId);

            return !currentDeviceId || !reading.device_id || reading.device_id === currentDeviceId;
        },
        normalizeReading(reading) {
            const id = Number.parseInt(reading.id || 0, 10);
            const deviceId = Number.parseInt(reading.device_id || 0, 10);
            const timestamp = reading.timestamp || reading.measured_at || reading.created_at || null;
            const x = this.timestampToMillis(timestamp);

            if (!Number.isFinite(x)) {
                return null;
            }

            return {
                id: Number.isFinite(id) ? id : 0,
                device_id: Number.isFinite(deviceId) && deviceId > 0 ? deviceId : null,
                x,
                air_temperature: this.toNullableNumber(reading.air_temperature),
                humidity: this.toNullableNumber(reading.humidity),
                water_temperature: this.toNullableNumber(reading.water_temperature),
                ph: this.toNullableNumber(reading.ph),
                ec: this.toNullableNumber(reading.ec),
                water_flow: this.toNullableNumber(reading.water_flow),
                water_level: this.toNullableNumber(reading.water_level),
            };
        },
        timestampToMillis(timestamp) {
            if (!timestamp) {
                return Number.NaN;
            }

            const millis = new Date(timestamp).getTime();
            return Number.isFinite(millis) ? millis : Number.NaN;
        },
        toNullableNumber(value) {
            if (value === null || value === undefined || value === '') {
                return null;
            }

            const parsed = Number.parseFloat(value);
            return Number.isFinite(parsed) ? parsed : null;
        },
        point(reading, key) {
            return { x: reading.x, y: reading[key] };
        },
        pushReading(reading) {
            this.overviewData.ph.push(this.point(reading, 'ph'));
            this.overviewData.waterTemp.push(this.point(reading, 'water_temperature'));
            this.overviewData.ec.push(this.point(reading, 'ec'));

            this.analyticsData.airTemp.push(this.point(reading, 'air_temperature'));
            this.analyticsData.humidity.push(this.point(reading, 'humidity'));
            this.analyticsData.waterFlow.push(this.point(reading, 'water_flow'));

            this.historyData.airTemp.push(this.point(reading, 'air_temperature'));
            this.historyData.humidity.push(this.point(reading, 'humidity'));
            this.historyData.waterTemp.push(this.point(reading, 'water_temperature'));
            this.historyData.ph.push(this.point(reading, 'ph'));
            this.historyData.ec.push(this.point(reading, 'ec'));
            this.historyData.waterFlow.push(this.point(reading, 'water_flow'));
            this.historyData.waterLevel.push(this.point(reading, 'water_level'));
        },
        trimSeries() {
            Object.values(this.overviewData).forEach((series) => this.trimSeriesArray(series));
            Object.values(this.analyticsData).forEach((series) => this.trimSeriesArray(series));
            Object.values(this.historyData).forEach((series) => this.trimSeriesArray(series));
        },
        trimSeriesArray(series) {
            while (series.length > this.maxPoints) {
                series.shift();
            }
        },
        resetSeries() {
            Object.keys(this.overviewData).forEach((key) => { this.overviewData[key] = []; });
            Object.keys(this.analyticsData).forEach((key) => { this.analyticsData[key] = []; });
            Object.keys(this.historyData).forEach((key) => { this.historyData[key] = []; });
            this.lastReadingId = 0;
        },
        resetKpis() {
            this.kpis = normalizeKpiState({});
            this.kpiPulse = emptyPulseState();
            Object.values(this.kpiPulseTimers).forEach((timer) => window.clearTimeout(timer));
            this.kpiPulseTimers = {};
        },
        seedSeries(payload) {
            const readings = Array.isArray(payload.telemetryChartReadings)
                ? payload.telemetryChartReadings
                : [];

            this.resetSeries();
            let latestSeededReading = null;

            readings.slice(-this.maxPoints).forEach((reading) => {
                const normalized = this.normalizeReading(reading);

                if (normalized === null || !this.readingBelongsToCurrentDevice(normalized)) {
                    return;
                }

                this.pushReading(normalized);
                this.lastReadingId = Math.max(this.lastReadingId, normalized.id || 0);
                latestSeededReading = normalized;
            });

            this.updateKpis(payload.telemetryKpis || payload.latest_kpis || this.buildKpisFromReading(latestSeededReading), false);
            this.trimSeries();
            this.hasChartTelemetry = Boolean(payload.hasChartTelemetry) || this.hasBufferedChartData();
        },
        hasBufferedChartData() {
            return [
                ...Object.values(this.overviewData),
                ...Object.values(this.analyticsData),
                ...Object.values(this.historyData),
            ].some((series) => series.some((point) => point.y !== null));
        },
        updateKpis(kpis, animate = true) {
            if (!kpis || typeof kpis !== 'object') {
                return;
            }

            const nextKpis = { ...this.kpis };
            const changedKeys = [];

            this.kpiKeys.forEach((key) => {
                if (!Object.prototype.hasOwnProperty.call(kpis, key)) {
                    return;
                }

                const previous = nextKpis[key] || emptyKpi();
                const next = {
                    value: kpis[key]?.value ?? previous.value ?? '--',
                    status: kpis[key]?.status ?? previous.status ?? 'Waiting',
                    statusType: kpis[key]?.statusType ?? previous.statusType ?? 'standby',
                    trend: kpis[key]?.trend ?? previous.trend ?? 'Waiting for sensor data...',
                };

                if (
                    next.value !== previous.value ||
                    next.status !== previous.status ||
                    next.statusType !== previous.statusType
                ) {
                    changedKeys.push(key);
                }

                nextKpis[key] = next;
            });

            this.kpis = nextKpis;

            if (animate) {
                changedKeys.forEach((key) => this.flashKpi(key));
            }
        },
        buildKpisFromReading(reading) {
            if (!reading) {
                return null;
            }

            return {
                air_temperature: this.valueOnlyKpi(reading.air_temperature, 1, 'air_temperature'),
                humidity: this.valueOnlyKpi(reading.humidity, 0, 'humidity'),
                water_temperature: this.valueOnlyKpi(reading.water_temperature, 1, 'water_temperature'),
                ph: this.valueOnlyKpi(reading.ph, 1, 'ph'),
                ec: this.valueOnlyKpi(reading.ec, 1, 'ec'),
                water_level: this.valueOnlyKpi(this.normalizeWaterLevel(reading.water_level), 0, 'water_level'),
                water_flow: this.valueOnlyKpi(reading.water_flow, 1, 'water_flow'),
            };
        },
        valueOnlyKpi(value, decimals, key) {
            const previous = this.kpis[key] || emptyKpi();
            const parsed = Number(value);

            return {
                value: value === null || value === undefined || !Number.isFinite(parsed) ? '--' : parsed.toFixed(decimals),
                status: previous.status,
                statusType: previous.statusType,
                trend: previous.trend,
            };
        },
        normalizeWaterLevel(value) {
            if (value === null || value === undefined) {
                return null;
            }

            return value >= 0 && value <= 1 ? value * 100 : value;
        },
        flashKpi(key) {
            if (this.kpiPulseTimers[key]) {
                window.clearTimeout(this.kpiPulseTimers[key]);
            }

            this.kpiPulse = { ...this.kpiPulse, [key]: true };
            this.kpiPulseTimers[key] = window.setTimeout(() => {
                this.kpiPulse = { ...this.kpiPulse, [key]: false };
                delete this.kpiPulseTimers[key];
            }, 300);
        },
        kpiValue(key) {
            return this.kpis[key]?.value ?? '--';
        },
        kpiStatusLabel(key) {
            return this.kpis[key]?.status ?? 'Waiting';
        },
        kpiTrend(key) {
            return this.kpis[key]?.trend ?? 'Waiting for sensor data...';
        },
        kpiCardClass(key) {
            return this.kpiPulse[key] ? 'ring-2 ring-[#95D5B2]/70 shadow-md' : '';
        },
        kpiValueClass(key) {
            return this.kpiPulse[key] ? 'text-[#2D6A4F] scale-[1.03]' : '';
        },
        kpiStatusBadgeClass(key) {
            const type = this.kpis[key]?.statusType || 'standby';

            if (['online', 'success', 'active', 'running'].includes(type)) {
                return 'bg-emerald-100 text-[#1B4332] border-emerald-200';
            }

            if (['offline', 'error', 'critical'].includes(type)) {
                return 'bg-rose-100 text-rose-800 border-rose-200';
            }

            if (type === 'warning') {
                return 'bg-amber-100 text-amber-900 border-amber-200';
            }

            if (type === 'info') {
                return 'bg-[#95D5B2]/30 text-[#1B4332] border-[#2D6A4F]/20';
            }

            return 'bg-slate-100 text-slate-700 border-slate-200';
        },
        kpiStatusDotClass(key) {
            const type = this.kpis[key]?.statusType || 'standby';

            if (['online', 'success', 'active', 'running'].includes(type)) {
                return 'bg-emerald-500 animate-pulse';
            }

            if (['offline', 'error', 'critical'].includes(type)) {
                return 'bg-rose-500';
            }

            if (type === 'warning') {
                return 'bg-amber-500';
            }

            if (type === 'info') {
                return 'bg-[#2D6A4F]';
            }

            return 'bg-slate-400';
        },
        updateApexCharts(payload) {
            if (!this.initialized) {
                this.$nextTick(() => this.renderOrUpdateCharts(payload || this.initialChartPayload()));
                return;
            }

            if (payload?.telemetryChartReadings) {
                this.seedSeries(payload);
                this.refreshMountedCharts(false);
            } else if (payload?.telemetryKpis) {
                this.updateKpis(payload.telemetryKpis, false);
            }
        },
        renderOrUpdateCharts(payload) {
            if (typeof ApexCharts === 'undefined') {
                return;
            }

            if (!this.initialized) {
                this.seedSeries(payload || this.initialChartPayload());
                this.renderCharts();
                this.initialized = true;
                return;
            }

            this.refreshMountedCharts(false);
        },
        renderCharts() {
            const chartEl = document.querySelector('#telemetryOverviewChart');
            if (chartEl && !this.charts.telemetryOverview) {
                this.charts.telemetryOverview = new ApexCharts(chartEl, this.telemetryOverviewOptions());
                this.charts.telemetryOverview.render();
            }

            const analyticsEl = document.querySelector('#analyticsMultiChart');
            if (analyticsEl && !this.charts.analytics) {
                this.charts.analytics = new ApexCharts(analyticsEl, this.analyticsOptions());
                this.charts.analytics.render();
            }

            const historyEl = document.querySelector('#telemetryHistoryChart');
            if (historyEl && !this.charts.history) {
                this.charts.history = new ApexCharts(historyEl, this.historyOptions());
                this.charts.history.render();
            }
        },
        refreshMountedCharts(animate = true) {
            if (this.charts.telemetryOverview) {
                this.charts.telemetryOverview.updateSeries(this.telemetryOverviewSeries(), animate);
            }

            if (this.charts.analytics) {
                this.charts.analytics.updateSeries(this.analyticsSeries(), animate);
            }

            if (this.charts.history) {
                this.charts.history.updateSeries(this.historySeries(), animate);
            }
        },
        baseChartOptions(height, extra = {}) {
            return {
                chart: {
                    height,
                    toolbar: { show: false },
                    fontFamily: 'Inter, sans-serif',
                    animations: {
                        enabled: true,
                        easing: 'linear',
                        speed: 250,
                        animateGradually: { enabled: false },
                        dynamicAnimation: { enabled: true, speed: 350 },
                    },
                    zoom: { enabled: false },
                },
                dataLabels: { enabled: false },
                markers: { size: 0, hover: { size: 4 } },
                xaxis: {
                    type: 'datetime',
                    labels: {
                        datetimeUTC: false,
                        style: { colors: '#1B4332' },
                    },
                    tooltip: { enabled: false },
                },
                yaxis: { labels: { style: { colors: '#1B4332' } } },
                tooltip: { x: { format: 'HH:mm:ss' } },
                grid: { borderColor: '#E5E7EB', strokeDashArray: 4 },
                legend: { position: 'top', horizontalAlign: 'right' },
                ...extra,
            };
        },
        telemetryOverviewOptions() {
            return this.baseChartOptions(280, {
                series: this.telemetryOverviewSeries(),
                chart: { ...this.baseChartOptions(280).chart, type: 'area' },
                colors: ['#2D6A4F', '#40916C', '#95D5B2'],
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] } },
                stroke: { curve: 'smooth', width: 2.5 },
            });
        },
        analyticsOptions() {
            return this.baseChartOptions(320, {
                series: this.analyticsSeries(),
                chart: { ...this.baseChartOptions(320).chart, type: 'line' },
                stroke: { width: [0, 3, 3], curve: 'smooth' },
                colors: ['#D8F3DC', '#2D6A4F', '#40916C'],
                plotOptions: { bar: { columnWidth: '40%', borderRadius: 6 } },
                grid: { borderColor: '#F1F5F9' },
            });
        },
        historyOptions() {
            return this.baseChartOptions(300, {
                series: this.historySeries(),
                chart: { ...this.baseChartOptions(300).chart, type: 'line' },
                colors: ['#1B4332', '#40916C', '#52B788', '#74C69D', '#95D5B2', '#2D6A4F', '#6B7280'],
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'solid', opacity: 0.08 },
            });
        },
        telemetryOverviewSeries() {
            return [
                { name: 'Water pH', data: [...this.overviewData.ph] },
                { name: 'Water Temp (°C)', data: [...this.overviewData.waterTemp] },
                { name: 'Nutrient EC (mS)', data: [...this.overviewData.ec] },
            ];
        },
        analyticsSeries() {
            return [
                { name: 'Air Temp (°C)', type: 'column', data: [...this.analyticsData.airTemp] },
                { name: 'Humidity (%)', type: 'line', data: [...this.analyticsData.humidity] },
                { name: 'Water Flow (L/min)', type: 'line', data: [...this.analyticsData.waterFlow] },
            ];
        },
        historySeries() {
            return [
                { name: 'Air Temp (°C)', data: [...this.historyData.airTemp] },
                { name: 'Humidity (%)', data: [...this.historyData.humidity] },
                { name: 'Water Temp (°C)', data: [...this.historyData.waterTemp] },
                { name: 'Water pH', data: [...this.historyData.ph] },
                { name: 'Nutrient EC (mS)', data: [...this.historyData.ec] },
                { name: 'Water Flow (L/min)', data: [...this.historyData.waterFlow] },
                { name: 'Water Level (%)', data: [...this.historyData.waterLevel] },
            ];
        },
    };
};

function updateDashboardClock() {
    const clockElement = document.getElementById('dashboard-clock-time');
    if (!clockElement) {
        return;
    }

    clockElement.textContent = new Date().toLocaleTimeString([], {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
}

function updateEsp32Status() {
    const statusDot = document.getElementById('esp32-status-dot');
    const statusLabel = document.getElementById('esp32-status-label');

    if (!statusDot || !statusLabel) {
        return;
    }

    fetch('/dashboard/esp32/status', {
        headers: {
            Accept: 'application/json',
        },
        credentials: 'same-origin',
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Failed to fetch ESP32 status');
            }
            return response.json();
        })
        .then((json) => {
            const online = Boolean(json.online);
            statusLabel.textContent = json.statusLabel || (online ? 'ESP32 Online' : 'ESP32 Offline');
            statusDot.className = `h-2.5 w-2.5 rounded-full ${online ? 'bg-[#2D6A4F]' : 'bg-rose-500'}`;
        })
        .catch((error) => {
            console.debug('ESP32 status refresh error:', error);
        });
}

document.addEventListener('DOMContentLoaded', () => {
    updateDashboardClock();
    updateEsp32Status();

    setInterval(updateDashboardClock, 1000);
    setInterval(updateEsp32Status, 5000);
});

