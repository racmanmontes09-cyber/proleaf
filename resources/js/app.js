import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

const envValue = (value) => {
    if (typeof value !== 'string') {
        return undefined;
    }

    const trimmed = value.trim();
    if (!trimmed || (trimmed.startsWith('${') && trimmed.endsWith('}'))) {
        return undefined;
    }

    return trimmed;
};

const isLoopbackHost = (host) => ['127.0.0.1', 'localhost', '::1', '[::1]'].includes(String(host || '').toLowerCase());
const pageHost = window.location.hostname;
const resolveBrowserRealtimeHost = (configuredHost, driver) => {
    if (configuredHost && isLoopbackHost(configuredHost) && pageHost && !isLoopbackHost(pageHost)) {
        return pageHost;
    }

    return configuredHost || (driver === 'reverb' ? pageHost : undefined);
};

const broadcastDriver = envValue(import.meta.env.VITE_BROADCAST_CONNECTION)
    || envValue(import.meta.env.VITE_BROADCAST_DRIVER)
    || 'reverb';
const reverbScheme = envValue(import.meta.env.VITE_REVERB_SCHEME)
    || envValue(import.meta.env.VITE_PUSHER_SCHEME)
    || 'http';
const configuredRealtimeHost = envValue(import.meta.env.VITE_REVERB_HOST)
    || envValue(import.meta.env.VITE_PUSHER_HOST);
const reverbHost = resolveBrowserRealtimeHost(configuredRealtimeHost, broadcastDriver);
const reverbPort = envValue(import.meta.env.VITE_REVERB_PORT)
    || envValue(import.meta.env.VITE_PUSHER_PORT)
    || (reverbScheme === 'https' ? 443 : 8080);
const broadcastKey = envValue(import.meta.env.VITE_REVERB_APP_KEY)
    || envValue(import.meta.env.VITE_PUSHER_APP_KEY);

const echoOptions = {
    broadcaster: broadcastDriver,
    key: broadcastKey,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    forceTLS: reverbScheme === 'https',
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
        },
    },
};

if (reverbHost) {
    echoOptions.wsHost = reverbHost;
}
if (reverbPort) {
    echoOptions.wsPort = Number(reverbPort);
    echoOptions.wssPort = Number(reverbPort);
}
echoOptions.enabledTransports = ['ws', 'wss'];

if (broadcastKey) {
    window.Echo = new Echo(echoOptions);
} else {
    console.warn('Echo not started: missing Vite broadcast app key.');
}

if (window.Echo?.connector?.pusher?.connection) {
    const pusherConnection = window.Echo.connector.pusher.connection;
    console.log('[Realtime] Echo connecting', { host: echoOptions.wsHost, port: echoOptions.wsPort, key: echoOptions.key });
    pusherConnection.bind('connected', () => {
        console.log('[Realtime] Echo connected', { state: pusherConnection.state });
    });
    pusherConnection.bind('error', (error) => {
        console.error('[Realtime] Echo connection error:', error);
    });
    pusherConnection.bind('disconnected', () => {
        console.warn('[Realtime] Echo disconnected');
    });
    pusherConnection.bind('connecting', () => {
        console.log('[Realtime] Echo reconnecting...');
    });
} else {
    console.warn('[Realtime] Echo connector not available');
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
    const parseNullableNumber = (value) => {
        if (value === null || value === undefined || value === '') {
            return null;
        }

        const parsed = Number.parseFloat(value);
        return Number.isFinite(parsed) ? parsed : null;
    };
    const threshold = (low, high, lowLabel = 'LOW', highLabel = 'HIGH') => ({
        low: parseNullableNumber(low),
        high: parseNullableNumber(high),
        lowLabel,
        highLabel,
    });
    const normalizeThresholds = (thresholds = {}) => ({
        air_temperature: threshold(thresholds.temperatureLow, thresholds.temperatureHigh),
        humidity: threshold(thresholds.humidityLow, thresholds.humidityHigh),
        water_temperature: threshold(thresholds.waterTemperatureLow, thresholds.waterTemperatureHigh),
        ph: threshold(thresholds.phLow, thresholds.phHigh),
        ec: threshold(thresholds.ecLow, thresholds.ecHigh),
        water_level: threshold(thresholds.waterLevelLow, thresholds.waterLevelHigh, 'LOW', 'FULL'),
        water_flow: threshold(thresholds.waterFlowLow, thresholds.waterFlowHigh, 'LOW FLOW', 'HIGH FLOW'),
    });

    return {
        activeTab: config.activeTab || 'dashboard',
        hasChartTelemetry: Boolean(config.hasChartTelemetry),
        initialized: false,
        subscribed: false,
        polling: false,
        pollTimer: null,
        useEchoTelemetry: Boolean(config.useEchoTelemetry),
        pollingFallbackEnabled: Boolean(config.pollingFallbackEnabled),
        debugTelemetryCharts: Boolean(config.debugTelemetryCharts),
        deviceOnline: Boolean(config.deviceOnline),
        onlineGraceMs: Number.parseInt(config.onlineGraceMs || 10000, 10),
        telemetryChannelName: null,
        telemetryHandler: null,
        realtimeConnected: false,
        destroyed: false,
        deviceId: config.deviceId || null,
        pollingUrl: config.pollingUrl || '/dashboard/telemetry/readings',
        maxPoints: Number.parseInt(config.maxPoints || 120, 10),
        bufferPoints: Number.parseInt(config.bufferPoints || 150, 10),
        pollIntervalMs: Number.parseInt(config.pollIntervalMs || 1000, 10),
        pollBatchLimit: Number.parseInt(config.pollBatchLimit || 120, 10),
        lastReadingId: 0,
        lastReadingTimestamp: Number.NEGATIVE_INFINITY,
        seenReadingIds: new Set(),
        loadingInitialHistory: false,
        historicalFrom: '',
        historicalTo: '',
        historicalPreset: 'custom',
        historicalFilterError: '',
        historicalHasData: false,
        kpiKeys,
        kpis: normalizeKpiState(config.initialKpis || config.initialChartPayload?.telemetryKpis),
        kpiPulse: emptyPulseState(),
        kpiPulseTimers: {},
        thresholds: normalizeThresholds(config.thresholds || {}),
        charts: {
            telemetryOverview: null,
            analytics: null,
            history: null,
        },
        isLive: true,
        selectedOverviewSensor: 'all',
        visibleWindowMs: 30 * 24 * 60 * 60 * 1000,
        overviewData: {
            ph: [],
            waterTemp: [],
            ec: [],
            airTemp: [],
            humidity: [],
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
                this.bootstrapTelemetryDashboard();
            });
        },
        logChartDebug(action, payload = {}) {
            if (!this.debugTelemetryCharts) {
                return;
            }

            console.debug(`[CHART] ${action}`, payload);
        },
        async bootstrapTelemetryDashboard() {
            const initialPayload = await this.fetchTelemetryHistoryPayload(this.initialChartPayload());

            if (this.destroyed) {
                return;
            }

            this.renderOrUpdateCharts(initialPayload);
            this.listenToPusher();

            if (!this.useEchoTelemetry) {
                console.log('[Realtime] Echo disabled, starting HTTP polling fallback');
                this.startTelemetryPolling();
            } else {
                console.log('[Realtime] Echo enabled, polling will only start if subscription fails');
                if (this.pollingFallbackEnabled) {
                    const fallbackDelayMs = 5000;
                    window.setTimeout(() => {
                        if (!this.destroyed && !this.realtimeConnected && !this.polling) {
                            console.log('[Realtime] Echo did not connect within', fallbackDelayMs, 'ms, starting polling fallback');
                            this.startTelemetryPolling();
                        }
                    }, fallbackDelayMs);
                }
            }
        },
        async fetchTelemetryHistoryPayload(fallbackPayload = {}) {
            if (!this.pollingUrl) {
                return fallbackPayload;
            }

            const requestedDeviceId = this.normalizeDeviceId(this.deviceId);
            this.loadingInitialHistory = true;

            try {
                const url = new URL(this.pollingUrl, window.location.origin);
                if (requestedDeviceId) {
                    url.searchParams.set('device_id', requestedDeviceId);
                }
                url.searchParams.set('limit', String(this.maxPoints));

                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });

                if (!response.ok) {
                    throw new Error(`Initial telemetry history failed with status ${response.status}`);
                }

                const json = await response.json();
                if (this.destroyed) {
                    return fallbackPayload;
                }
                if (requestedDeviceId && this.normalizeDeviceId(this.deviceId) !== requestedDeviceId) {
                    return fallbackPayload;
                }

                this.applyResponseDeviceId(json.device_id, false);

                return this.chartPayloadFromTelemetryResponse(json, fallbackPayload);
            } catch (error) {
                console.debug('Initial telemetry history error:', error);
                return fallbackPayload;
            } finally {
                this.loadingInitialHistory = false;
            }
        },
        chartPayloadFromTelemetryResponse(json, fallbackPayload = {}) {
            const responseHasReadings = Array.isArray(json?.readings);
            const readings = responseHasReadings ? json.readings : [];

            return {
                ...fallbackPayload,
                telemetryChartReadings: readings,
                telemetryKpis: json?.latest_kpis || fallbackPayload.telemetryKpis || null,
                hasChartTelemetry: responseHasReadings ? readings.length > 0 : Boolean(fallbackPayload.hasChartTelemetry),
            };
        },
        listenToPusher() {
            if (!this.useEchoTelemetry || typeof window.Echo === 'undefined' || this.subscribed || !this.deviceId) {
                console.log('[Realtime] listenToPusher skipped', {
                    useEchoTelemetry: this.useEchoTelemetry,
                    echoDefined: typeof window.Echo !== 'undefined',
                    alreadySubscribed: this.subscribed,
                    deviceId: this.deviceId,
                });
                return;
            }

            const channelName = `devices.${this.deviceId}.telemetry`;
            if (this.telemetryChannelName && this.telemetryChannelName !== channelName) {
                this.leaveTelemetryChannel();
            }

            this.subscribed = true;
            this.telemetryChannelName = channelName;
            this.telemetryHandler = (data) => this.handleTelemetryReceived(data);

            console.log('[Realtime] subscribing to', channelName);
            const channel = window.Echo.private(channelName);
            channel.listen('.TelemetryReceived', this.telemetryHandler);

            channel.subscribed(() => {
                if (this.destroyed) {
                    return;
                }
                this.realtimeConnected = true;
                console.log('[Realtime] subscribed to', channelName);
            });

            channel.error((error) => {
                this.realtimeConnected = false;
                this.subscribed = false;
                console.error('[Realtime] subscription error:', channelName, error);
                if (this.pollingFallbackEnabled) {
                    this.startTelemetryPolling();
                }
            });

            channel.listen('.broadcast-reverb-connected', () => {
                console.log('[Realtime] Reverb confirmed connected');
            });
        },
        leaveTelemetryChannel() {
            if (this.telemetryChannelName && typeof window.Echo !== 'undefined') {
                console.log('[Realtime] leaving channel', this.telemetryChannelName);
                window.Echo.leave(this.telemetryChannelName);
            }

            this.telemetryChannelName = null;
            this.telemetryHandler = null;
            this.subscribed = false;
            this.realtimeConnected = false;
        },
        shouldUseTelemetryPolling() {
            if (this.pollingFallbackEnabled) {
                return true;
            }

            return Boolean(this.pollingUrl);
        },
        startTelemetryPolling() {
            if (this.destroyed || this.pollTimer) {
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
            if (this.destroyed) {
                return;
            }

            this.stopTelemetryPolling();
            this.pollTimer = window.setTimeout(() => this.pollTelemetryReadings(), delay);
        },
        async pollTelemetryReadings(options = {}) {
            const reschedule = options.reschedule ?? true;

            if (this.destroyed) {
                return;
            }

            if (this.polling) {
                if (reschedule && this.shouldUseTelemetryPolling()) {
                    this.scheduleNextTelemetryPoll();
                }
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
                if (this.destroyed) {
                    return;
                }

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
                if (!this.destroyed && reschedule && this.shouldUseTelemetryPolling()) {
                    this.scheduleNextTelemetryPoll();
                }
            }
        },
        normalizeDeviceId(deviceId) {
            const parsed = Number.parseInt(deviceId || 0, 10);
            return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
        },
        applyResponseDeviceId(deviceId, subscribe = true) {
            const nextDeviceId = this.normalizeDeviceId(deviceId);
            if (!nextDeviceId) {
                return;
            }

            const currentDeviceId = this.normalizeDeviceId(this.deviceId);
            if (!currentDeviceId) {
                this.deviceId = nextDeviceId;
                if (subscribe) {
                    this.listenToPusher();
                }
                return;
            }

            if (currentDeviceId !== nextDeviceId) {
                this.switchTelemetryDevice(nextDeviceId, false, subscribe);
            }
        },
        switchTelemetryDevice(deviceId, restartPoll = true, subscribe = true) {
            const nextDeviceId = this.normalizeDeviceId(deviceId);
            const currentDeviceId = this.normalizeDeviceId(this.deviceId);

            if (nextDeviceId === currentDeviceId) {
                return;
            }

            this.isLive = true;
            this.leaveTelemetryChannel();
            this.deviceId = nextDeviceId;
            this.hasChartTelemetry = false;
            this.resetSeries();
            this.resetKpis();
            this.refreshMountedCharts(false);
            if (subscribe) {
                this.listenToPusher();
            }

            if (restartPoll) {
                this.fetchTelemetryHistoryPayload({
                    telemetryChartReadings: [],
                    telemetryKpis: null,
                    hasChartTelemetry: false,
                }).then((payload) => {
                    if (this.normalizeDeviceId(this.deviceId) !== nextDeviceId) {
                        return;
                    }

                    this.seedSeries(payload);
                    this.refreshMountedCharts(false);
                });
            }

            if (restartPoll && this.shouldUseTelemetryPolling()) {
                this.scheduleNextTelemetryPoll(0);
            }
        },
        handleTelemetryReceived(data) {
            if (!data) {
                return;
            }

            if (!this.deviceOnline) {
                this.deviceOnline = true;
            }
            clearTimeout(this._staleTimer);
            this._staleTimer = setTimeout(() => {
                this.deviceOnline = false;
                this.applyOfflineStatus();
            }, this.onlineGraceMs);

            this.handleTelemetryReadings([data]);
        },
        handleTelemetryReadings(readings, latestKpis = null) {
            if (this.destroyed) {
                return;
            }

            const normalized = readings
                .map((reading) => this.normalizeReading(reading))
                .filter((reading) => reading !== null && this.readingBelongsToCurrentDevice(reading))
                .sort((a, b) => (a.x - b.x) || (a.id - b.id));

            if (normalized.length === 0) {
                return;
            }

            let changed = false;
            let latestChartableReading = null;
            let nextKpis = null;

            if (this.hasMeaningfulKpiPayload(latestKpis)) {
                nextKpis = latestKpis;
            }

            normalized.forEach((reading) => {
                if (this.hasSeenReading(reading)) {
                    return;
                }

                this.rememberReading(reading);
                this.lastReadingId = Math.max(this.lastReadingId, reading.id || 0);
                this.lastReadingTimestamp = Math.max(this.lastReadingTimestamp, reading.x);

                if (!this.hasMeaningfulTelemetryValues(reading)) {
                    return;
                }

                this.pushReading(reading);
                latestChartableReading = reading;
                changed = true;
            });

            if (changed) {
                this.sortSeriesByTimestamp();
                this.trimSeries();
            }

            if (changed || nextKpis) {
                this.hasChartTelemetry = true;
                if (!nextKpis) {
                    nextKpis = this.buildKpisFromReading(latestChartableReading);
                }

                if (nextKpis) {
                    this.updateKpis(nextKpis);
                }

                if (this.initialized) {
                    this.refreshMountedCharts(false, changed ? 'live-tick' : 'kpi-only');
                }
            }
        },

        hasSeenReading(reading) {
            return reading.id > 0 && this.seenReadingIds.has(reading.id);
        },
        rememberReading(reading) {
            if (reading.id <= 0) {
                return;
            }

            this.seenReadingIds.add(reading.id);
            if (this.seenReadingIds.size > this.maxPoints * 4) {
                const oldest = this.seenReadingIds.values().next().value;
                this.seenReadingIds.delete(oldest);
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
        hasMeaningfulTelemetryValues(reading) {
            if (!reading) {
                return false;
            }

            return [
                reading.air_temperature,
                reading.humidity,
                reading.water_temperature,
                reading.ph,
                reading.ec,
                reading.water_flow,
                reading.water_level,
            ].some((value) => value !== null && value !== undefined);
        },
        hasMeaningfulKpiPayload(kpis) {
            if (!kpis || typeof kpis !== 'object') {
                return false;
            }

            return this.kpiKeys.filter((key) => key !== 'water_flow').some((key) => {
                const entry = kpis[key];
                const value = entry?.value;
                return value !== undefined && value !== null && value !== '--';
            });
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
            return { x: reading.x, y: reading[key], id: reading.id || 0 };
        },
        pushReading(reading) {
            this.overviewData.ph.push(this.point(reading, 'ph'));
            this.overviewData.waterTemp.push(this.point(reading, 'water_temperature'));
            this.overviewData.ec.push(this.point(reading, 'ec'));
            this.overviewData.airTemp.push(this.point(reading, 'air_temperature'));
            this.overviewData.humidity.push(this.point(reading, 'humidity'));

            this.analyticsData.airTemp.push(this.point(reading, 'air_temperature'));
            this.analyticsData.humidity.push(this.point(reading, 'humidity'));
            this.analyticsData.waterFlow.push(this.point(reading, 'water_flow'));

            this.pushHistoryReading(reading);
        },
        pushHistoryReading(reading) {
            this.historyData.airTemp.push(this.point(reading, 'air_temperature'));
            this.historyData.humidity.push(this.point(reading, 'humidity'));
            this.historyData.waterTemp.push(this.point(reading, 'water_temperature'));
            this.historyData.ph.push(this.point(reading, 'ph'));
            this.historyData.ec.push(this.point(reading, 'ec'));
            this.historyData.waterFlow.push(this.point(reading, 'water_flow'));
            this.historyData.waterLevel.push(this.point(reading, 'water_level'));
        },
        trimSeries() {
            let trimmed = false;

            Object.values(this.overviewData).forEach((series) => {
                trimmed = this.trimSeriesArray(series) || trimmed;
            });
            Object.values(this.analyticsData).forEach((series) => {
                trimmed = this.trimSeriesArray(series) || trimmed;
            });
            Object.values(this.historyData).forEach((series) => {
                trimmed = this.trimSeriesArray(series) || trimmed;
            });

            if (trimmed) {
                this.logChartDebug('trim', {
                    visiblePoints: this.maxPoints,
                    bufferPoints: this.chartBufferLimit(),
                });
            }

            return trimmed;
        },
        sortSeriesByTimestamp() {
            this.logChartDebug('reorder', {});
            [
                ...Object.values(this.overviewData),
                ...Object.values(this.analyticsData),
                ...Object.values(this.historyData),
            ].forEach((series) => {
                series.sort((a, b) => (a.x - b.x) || ((a.id || 0) - (b.id || 0)));
            });
        },
        chartBufferLimit() {
            return Math.max(this.maxPoints * 3, Number.isFinite(this.bufferPoints) ? this.bufferPoints : this.maxPoints);
        },
        trimSeriesArray(series) {
            if (series.length <= this.chartBufferLimit()) {
                return false;
            }

            while (series.length > this.chartBufferLimit()) {
                series.shift();
            }
            return true;
        },
        resetSeries() {
            Object.keys(this.overviewData).forEach((key) => { this.overviewData[key] = []; });
            Object.keys(this.analyticsData).forEach((key) => { this.analyticsData[key] = []; });
            Object.keys(this.historyData).forEach((key) => { this.historyData[key] = []; });
            this.lastReadingId = 0;
            this.lastReadingTimestamp = Number.NEGATIVE_INFINITY;
            this.seenReadingIds.clear();
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

                this.rememberReading(normalized);
                this.lastReadingId = Math.max(this.lastReadingId, normalized.id || 0);
                this.lastReadingTimestamp = Math.max(this.lastReadingTimestamp, normalized.x);

                if (!this.hasMeaningfulTelemetryValues(normalized)) {
                    return;
                }

                this.pushReading(normalized);
                latestSeededReading = normalized;
            });

            const nextKpis = this.hasMeaningfulKpiPayload(payload.telemetryKpis)
                ? payload.telemetryKpis
                : (this.hasMeaningfulKpiPayload(payload.latest_kpis)
                    ? payload.latest_kpis
                    : this.buildKpisFromReading(latestSeededReading));

            if (nextKpis) {
                this.updateKpis(nextKpis, false);
            }
            this.trimSeries();
            this.hasChartTelemetry = this.hasBufferedChartData();
            this.historicalHasData = this.historyDataHasData();
        },
        historyDataHasData() {
            return Object.values(this.historyData).some((series) => series.some((point) => point.y !== null));
        },
        formatDateTimeLocal(date) {
            const pad = (value) => String(value).padStart(2, '0');
            return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
        },
        setHistoricalPreset(preset) {
            const now = new Date();
            this.historicalPreset = preset;
            this.historicalFilterError = '';

            if (preset === 'custom') {
                return;
            }

            const from = new Date(now);
            if (preset === 'today') {
                from.setHours(0, 0, 0, 0);
            } else if (preset === '24h') {
                from.setHours(from.getHours() - 24);
            } else if (preset === '7d') {
                from.setDate(from.getDate() - 7);
            } else if (preset === '30d') {
                from.setDate(from.getDate() - 30);
            }

            this.historicalFrom = this.formatDateTimeLocal(from);
            this.historicalTo = this.formatDateTimeLocal(now);
            this.applyHistoricalFilter();
        },
        historicalRangeLabel() {
            if (!this.historicalFrom && !this.historicalTo) {
                const now = new Date();
                const from = new Date(now);
                from.setDate(from.getDate() - 30);
                const format = (date) => date.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });

                return `${format(from)} to ${format(now)}`;
            }

            const format = (value) => {
                if (!value) return 'Beginning';
                const date = new Date(value);
                return Number.isNaN(date.getTime()) ? value : date.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
            };

            return `${format(this.historicalFrom)} to ${format(this.historicalTo)}`;
        },
        async applyHistoricalFilter() {
            this.historicalFilterError = '';
            if ((this.historicalFrom && !this.historicalTo) || (!this.historicalFrom && this.historicalTo)) {
                this.historicalFilterError = 'Select both a start and end date/time.';
                return;
            }

            if (this.historicalFrom && this.historicalTo && new Date(this.historicalFrom) > new Date(this.historicalTo)) {
                this.historicalFilterError = 'The start date/time must be before the end date/time.';
                return;
            }

            const url = new URL(this.pollingUrl, window.location.origin);
            const requestedDeviceId = this.normalizeDeviceId(this.deviceId);
            if (requestedDeviceId) url.searchParams.set('device_id', requestedDeviceId);
            if (this.historicalFrom) url.searchParams.set('from', this.historicalFrom);
            if (this.historicalTo) url.searchParams.set('to', this.historicalTo);
            url.searchParams.set('limit', String(this.maxPoints));

            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
                const json = await response.json();
                if (!response.ok) {
                    this.historicalFilterError = Object.values(json.errors || {}).flat()[0] || 'Unable to load the selected date/time range.';
                    return;
                }

                this.seedSeries({ telemetryChartReadings: Array.isArray(json.readings) ? json.readings : [] });
                this.refreshMountedCharts(false, 'historical-filter');
            } catch (error) {
                this.historicalFilterError = 'Unable to load the selected date/time range.';
            }
        },
        hasBufferedChartData() {
            return [
                ...Object.values(this.overviewData),
                ...Object.values(this.analyticsData),
                ...Object.values(this.historyData),
            ].some((series) => series.some((point) => point.y !== null));
        },
        applyOfflineStatus() {
            const nextKpis = { ...this.kpis };
            this.kpiKeys.forEach((key) => {
                const prev = nextKpis[key] || emptyKpi();
                nextKpis[key] = { ...prev, status: 'Disconnected', statusType: 'danger' };
            });
            this.kpis = nextKpis;
        },
        onChartZoomed(xaxis) {
            if (!xaxis || !this.lastReadingTimestamp || this.lastReadingTimestamp === Number.NEGATIVE_INFINITY) {
                return;
            }
            const rightEdge = xaxis.max || 0;
            const now = Date.now();
            const liveThreshold = 5000;
            if (now - rightEdge > liveThreshold) {
                this.isLive = false;
            }
        },
        goToLive() {
            this.isLive = true;
            this.refreshMountedCharts(false, 'go-to-live');
        },
        filterOverviewSensor() {
            if (!this.charts.telemetryOverview) {
                return;
            }

            this.charts.telemetryOverview.updateSeries(this.telemetryOverviewSeries(), false);
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
                const incoming = kpis[key] || {};
                const hasIncomingValue = incoming.value !== undefined && incoming.value !== null && incoming.value !== '--' || key === 'water_flow';
                const hasIncomingStatus = incoming.status !== undefined && incoming.status !== null && incoming.status !== 'Waiting';
                const hasIncomingStatusType = incoming.statusType !== undefined && incoming.statusType !== null && incoming.statusType !== 'standby';
                const hasIncomingTrend = incoming.trend !== undefined && incoming.trend !== null && incoming.trend !== 'Waiting for sensor data...';
                const next = {
                    value: hasIncomingValue ? incoming.value : (previous.value ?? '--'),
                    status: hasIncomingStatus ? incoming.status : (previous.status ?? 'Waiting'),
                    statusType: hasIncomingStatusType ? incoming.statusType : (previous.statusType ?? 'standby'),
                    trend: hasIncomingTrend ? incoming.trend : (previous.trend ?? 'Waiting for sensor data...'),
                };

                if (!this.deviceOnline) {
                    next.status = 'Disconnected';
                    next.statusType = 'danger';
                }

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

            const trend = this.trendTextFromTimestamp(reading.x);
            const result = {};

            const assignIfPresent = (key, value, decimals, allowHeartbeatValue = false) => {
                const parsed = Number(value);
                if (value === null || value === undefined || !Number.isFinite(parsed)) {
                    return;
                }

                if (!allowHeartbeatValue && key === 'water_flow' && !this.hasMeaningfulTelemetryValues(reading)) {
                    return;
                }

                result[key] = this.valueOnlyKpi(parsed, decimals, key, trend);
            };

            assignIfPresent('air_temperature', reading.air_temperature, 1);
            assignIfPresent('humidity', reading.humidity, 0);
            assignIfPresent('water_temperature', reading.water_temperature, 1);
            assignIfPresent('ph', reading.ph, 1);
            assignIfPresent('ec', reading.ec, 1);
            assignIfPresent('water_level', this.normalizeWaterLevel(reading.water_level), 0);
            assignIfPresent('water_flow', reading.water_flow, 1);

            if (reading.water_flow === null || reading.water_flow === undefined) {
                result['water_flow'] = { value: '--', status: 'OFFLINE', statusType: 'danger', trend: this.trendTextFromTimestamp(reading.x) };
            }

            return result;
        },
        valueOnlyKpi(value, decimals, key, trendText = null) {
            const previous = this.kpis[key] || emptyKpi();
            const parsed = Number(value);
            const hasValue = value !== null && value !== undefined && Number.isFinite(parsed);
            const status = this.resolveKpiStatus(key, hasValue ? parsed : null);

            return {
                value: hasValue ? parsed.toFixed(decimals) : '--',
                status: status.status ?? previous.status,
                statusType: status.statusType ?? previous.statusType,
                trend: trendText ?? previous.trend,
            };
        },
        resolveKpiStatus(key, value) {
            if (!this.deviceOnline) {
                return { status: 'Disconnected', statusType: 'danger' };
            }

            if (key === 'water_flow') {
                if (value === null || !Number.isFinite(value)) {
                    return { status: 'OFFLINE', statusType: 'danger' };
                }
                return { status: 'ONLINE', statusType: 'online' };
            }

            const thresholdConfig = this.thresholds[key] || {};
            const low = thresholdConfig.low;
            const high = thresholdConfig.high;

            if (value === null || !Number.isFinite(value) || !Number.isFinite(low) || !Number.isFinite(high)) {
                return { status: 'Waiting', statusType: 'standby' };
            }

            if (value < low) {
                return { status: thresholdConfig.lowLabel || 'LOW', statusType: 'warning' };
            }

            if (value > high) {
                return { status: thresholdConfig.highLabel || 'HIGH', statusType: 'warning' };
            }

            return { status: 'NORMAL', statusType: 'online' };
        },
        trendTextFromTimestamp(timestamp) {
            if (!Number.isFinite(timestamp)) {
                return null;
            }

            const elapsedSeconds = Math.max(0, Math.floor((Date.now() - timestamp) / 1000));
            if (elapsedSeconds < 5) {
                return 'Last Updated: just now';
            }

            if (elapsedSeconds < 60) {
                return `Last Updated: ${elapsedSeconds}s ago`;
            }

            const elapsedMinutes = Math.floor(elapsedSeconds / 60);
            if (elapsedMinutes < 60) {
                return `Last Updated: ${elapsedMinutes}m ago`;
            }

            const elapsedHours = Math.floor(elapsedMinutes / 60);
            return `Last Updated: ${elapsedHours}h ago`;
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
                return 'text-emerald-700';
            }

            if (['offline', 'error', 'critical'].includes(type)) {
                return 'text-rose-700';
            }

            if (type === 'warning') {
                return 'text-amber-700';
            }

            if (type === 'info') {
                return 'text-[#2D6A4F]';
            }

            return 'text-slate-600';
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
            if (this.destroyed) {
                return;
            }

            if (!this.initialized) {
                this.$nextTick(() => this.renderOrUpdateCharts(payload || this.initialChartPayload()));
                return;
            }

            if (payload?.telemetryChartReadings) {
                this.seedSeries(payload);
                this.refreshMountedCharts(false, 'livewire-reconcile');
            } else if (payload?.telemetryKpis) {
                this.updateKpis(payload.telemetryKpis, false);
            }
        },
        renderOrUpdateCharts(payload) {
            if (this.destroyed) {
                return;
            }

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
            if (this.destroyed) {
                return;
            }

            const chartEl = document.querySelector('#telemetryOverviewChart');
            if (chartEl && !this.charts.telemetryOverview) {
                this.logChartDebug('render', { chart: 'telemetryOverview' });
                this.charts.telemetryOverview = new ApexCharts(chartEl, this.telemetryOverviewOptions());
                this.charts.telemetryOverview.render();
            }

            const analyticsEl = document.querySelector('#analyticsMultiChart');
            if (analyticsEl && !this.charts.analytics) {
                this.logChartDebug('render', { chart: 'analytics' });
                this.charts.analytics = new ApexCharts(analyticsEl, this.analyticsOptions());
                this.charts.analytics.render();
            }

            const historyEl = document.querySelector('#telemetryHistoryChart');
            if (historyEl && !this.charts.history) {
                this.logChartDebug('render', { chart: 'history' });
                this.charts.history = new ApexCharts(historyEl, this.historyOptions());
                this.charts.history.render();
            }
        },
        refreshMountedCharts(animate = true, reason = 'manual') {
            if (this.destroyed) {
                return;
            }

            const doUpdate = () => {
                const xaxisRange = this.chartXaxisRange();

                const charts = [
                    { instance: this.charts.telemetryOverview, name: 'telemetryOverview', seriesFn: () => this.telemetryOverviewSeries() },
                    { instance: this.charts.analytics, name: 'analytics', seriesFn: () => this.analyticsSeries() },
                    { instance: this.charts.history, name: 'history', seriesFn: () => this.historySeries() },
                ];

                charts.forEach(({ instance, name, seriesFn }) => {
                    if (!instance) {
                        return;
                    }
                    this.logChartDebug('updateOptions', { chart: name, reason });
                    const series = seriesFn();
                    try {
                        const opts = { series };
                        opts.xaxis = xaxisRange;
                        instance.updateOptions(opts, false, false, false);
                    } catch (e) {
                        /* swallow */
                    }
                });
            };

            if (typeof requestAnimationFrame !== 'undefined') {
                requestAnimationFrame(doUpdate);
            } else {
                doUpdate();
            }
        },
        chartXaxisRange() {
            const timestamps = [
                ...Object.values(this.overviewData),
                ...Object.values(this.analyticsData),
                ...Object.values(this.historyData),
            ]
                .flat()
                .map((point) => point.x)
                .filter((timestamp) => Number.isFinite(timestamp));

            if (timestamps.length === 0) {
                const now = Date.now();
                return { min: now - this.visibleWindowMs, max: now };
            }

            const earliest = Math.min(...timestamps);
            const latest = Math.max(...timestamps);
            const now = Date.now();

            return {
                min: this.isLive ? Math.min(earliest, now - this.visibleWindowMs) : earliest,
                max: this.isLive ? Math.max(latest, now) : latest,
            };
        },
        destroyCharts() {
            Object.values(this.charts).forEach((chart) => {
                if (chart && typeof chart.destroy === 'function') {
                    try {
                        this.logChartDebug('destroy', {});
                        chart.destroy();
                    } catch (error) {
                        console.debug('ApexCharts destroy error:', error);
                    }
                }
            });

            this.charts = {
                telemetryOverview: null,
                analytics: null,
                history: null,
            };
        },
        destroy() {
            console.log('[Realtime] destroying telemetry dashboard');
            this.destroyed = true;
            this.stopTelemetryPolling();
            this.leaveTelemetryChannel();
            this.destroyCharts();
            Object.values(this.kpiPulseTimers).forEach((timer) => window.clearTimeout(timer));
            this.kpiPulseTimers = {};
        },
        baseChartOptions(height, extra = {}) {
            return {
                chart: {
                    height,
                    fontFamily: 'Inter, sans-serif',
                    animations: {
                        enabled: false,
                    },
                    zoom: {
                        enabled: true,
                        type: 'x',
                        autoScaleYaxis: true,
                        zoomedChartWindow: 10,
                    },
                    panning: {
                        enabled: true,
                        enableThreshold: 5,
                    },
                    brush: {
                        enabled: false,
                    },
                    selection: {
                        enabled: true,
                        type: 'x',
                        fill: { color: '#2D6A4F', opacity: 0.1 },
                        stroke: { color: '#2D6A4F', width: 1, dashArray: 3 },
                    },
                    events: {
                        zoomed: (chartContext, { xaxis }) => {
                            this.onChartZoomed(xaxis);
                        },
                        selection: (event, chartContext, config) => {
                            if (config?.xaxis) {
                                this.onChartZoomed(config.xaxis);
                            }
                            return false;
                        },
                    },
                    toolbar: { show: false },
                },
                dataLabels: { enabled: false },
                markers: { size: 0, hover: { size: 4 } },
                xaxis: {
                    type: 'datetime',
                    labels: {
                        datetimeUTC: false,
                        style: { colors: '#1B4332', fontSize: '10px' },
                        formatter(value) {
                            const d = new Date(value);
                            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                            return `${days[d.getDay()]}, ${months[d.getMonth()]} ${d.getDate()}`;
                        },
                    },
                    crosshairs: {
                        show: true,
                        stroke: { color: '#2D6A4F', width: 1, dashArray: 3 },
                    },
                    tooltip: { enabled: false },
                    axisBorder: { show: true, color: '#E5E7EB' },
                    axisTicks: { show: true, color: '#E5E7EB' },
                },
                yaxis: { labels: { style: { colors: '#1B4332', fontSize: '10px' } } },
                tooltip: {
                    x: {
                        formatter(value) {
                            const d = new Date(value);
                            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                            return `${days[d.getDay()]}, ${months[d.getMonth()]} ${d.getDate()}`;
                        },
                    },
                    theme: 'light',
                },
                grid: { borderColor: '#E5E7EB', strokeDashArray: 4 },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontSize: window.innerWidth < 640 ? '7px' : '10px',
                },
                ...extra,
            };
        },
        telemetryOverviewOptions() {
            return this.baseChartOptions(280, {
                series: this.telemetryOverviewSeries(),
                chart: { ...this.baseChartOptions(280).chart, type: 'area' },
                colors: ['#2D6A4F', '#40916C', '#95D5B2', '#E76F51', '#264653'],
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] } },
                stroke: { curve: 'smooth', width: 2.5 },
                xaxis: { ...this.baseChartOptions(280).xaxis, ...this.chartXaxisRange() },
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
                xaxis: { ...this.baseChartOptions(320).xaxis, ...this.chartXaxisRange() },
            });
        },
        historyOptions() {
            return this.baseChartOptions(300, {
                series: this.historySeries(),
                chart: { ...this.baseChartOptions(300).chart, type: 'line' },
                colors: ['#1B4332', '#40916C', '#52B788', '#74C69D', '#95D5B2', '#2D6A4F', '#6B7280'],
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'solid', opacity: 0.08 },
                xaxis: { ...this.baseChartOptions(300).xaxis, ...this.chartXaxisRange() },
            });
        },
        telemetryOverviewSeries() {
            const series = [
                { name: 'Water pH', data: [...this.overviewData.ph] },
                { name: 'Water Temp (°C)', data: [...this.overviewData.waterTemp] },
                { name: 'Nutrient EC (mS)', data: [...this.overviewData.ec] },
                { name: 'Air Temp (°C)', data: [...this.overviewData.airTemp] },
                { name: 'Humidity (%)', data: [...this.overviewData.humidity] },
            ];

            if (this.selectedOverviewSensor === 'all') {
                return series;
            }

            return series.filter((entry) => entry.name === this.selectedOverviewSensor);
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
    const dateElement = document.getElementById('dashboard-date');
    const now = new Date();

    if (clockElement) {
        clockElement.textContent = now.toLocaleTimeString([], {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        });
    }

    if (dateElement) {
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        dateElement.textContent = `${days[now.getDay()]}, ${months[now.getMonth()]} ${now.getDate()}`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    updateDashboardClock();
    setInterval(updateDashboardClock, 1000);
});

