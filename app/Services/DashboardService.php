<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Telemetry;
use App\Services\DeviceCommandService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function __construct(
        protected ThresholdService $thresholdService,
        protected DeviceStatusService $deviceStatusService,
        protected TelemetryService $telemetryService,
        protected AlertService $alertService,
        protected DeviceCommandService $deviceCommandService
    ) {}

    /**
     * Load and assemble all data required for the main DeviceStatus Dashboard.
     */
    public function getDashboardData(
        string $alertSeverityFilter = 'all',
        bool $includeCharts = true,
        ?\App\Models\Greenhouse $greenhouse = null,
        ?Device $targetDevice = null
    ): array {
        $thresholds = $this->thresholdService->getAllThresholds();
        $devices = $this->telemetryService->getDevicesWithTelemetries(2);

        $device = $targetDevice ?? $this->resolveDashboardDevice($devices, $greenhouse);
        $telemetryHistory = $includeCharts ? $this->getTelemetryHistoryForDashboard($device) : collect();
        $latestTelemetry = $includeCharts && $telemetryHistory->isNotEmpty()
            ? $telemetryHistory->last()
            : $this->getLatestTelemetryForDashboard($device);

        $deviceStatusLabel = $this->deviceStatusService->getDeviceStatusLabel($device);
        $deviceStatusType = $this->deviceStatusService->getDeviceStatusType($device);
        $bannerStatusLabel = $this->deviceStatusService->getBannerStatusLabel($device);
        $lastUpdatedLabel = $this->deviceStatusService->getLastUpdatedLabel($device);
        $deviceNameLabel = $this->deviceStatusService->getDeviceNameLabel($device);
        $deviceIdLabel = $this->deviceStatusService->getDeviceIdLabel($device);
        $firmwareLabel = $this->deviceStatusService->getFirmwareLabel($device);
        $localIpLabel = $this->deviceStatusService->getLocalIpLabel($device);
        $wifiRssiLabel = $this->deviceStatusService->getWifiRssiLabel($device);
        $lastSeenLabel = $this->deviceStatusService->getLastSeenLabel($device);
        $systemUptimeLabel = $this->deviceStatusService->getSystemUptimeLabel($device);
        $batteryStatusLabel = $this->deviceStatusService->resolveBatteryStatusLabel($device, $latestTelemetry);

        $telemetryTimestamp = $latestTelemetry?->measured_at ?? $latestTelemetry?->updated_at ?? $latestTelemetry?->created_at;
        $trendText = $telemetryTimestamp
            ? 'Last Updated: '.$telemetryTimestamp->diffForHumans()
            : 'Waiting for sensor data...';

        // Ambient Temperature
        $airTempVal = $latestTelemetry?->air_temperature;
        $temperatureValue = $airTempVal !== null ? number_format((float) $airTempVal, 1) : '--';
        $temperatureStatusLabel = $this->thresholdService->resolveStatus($airTempVal !== null ? (float) $airTempVal : null, $thresholds['temperatureLow'], $thresholds['temperatureHigh']);
        $temperatureStatusType = $this->thresholdService->resolveStatusType($airTempVal !== null ? (float) $airTempVal : null, $thresholds['temperatureLow'], $thresholds['temperatureHigh']);

        // Relative Humidity
        $humVal = $latestTelemetry?->humidity;
        $humidityValue = $humVal !== null ? number_format((float) $humVal, 0) : '--';
        $humidityStatusLabel = $this->thresholdService->resolveStatus($humVal !== null ? (float) $humVal : null, $thresholds['humidityLow'], $thresholds['humidityHigh']);
        $humidityStatusType = $this->thresholdService->resolveStatusType($humVal !== null ? (float) $humVal : null, $thresholds['humidityLow'], $thresholds['humidityHigh']);

        // Water Temperature
        $wTempVal = $latestTelemetry?->water_temperature;
        $waterTemperatureValue = $wTempVal !== null ? number_format((float) $wTempVal, 1) : '--';
        $waterTemperatureStatusLabel = $this->thresholdService->resolveStatus($wTempVal !== null ? (float) $wTempVal : null, $thresholds['waterTemperatureLow'], $thresholds['waterTemperatureHigh']);
        $waterTemperatureStatusType = $this->thresholdService->resolveStatusType($wTempVal !== null ? (float) $wTempVal : null, $thresholds['waterTemperatureLow'], $thresholds['waterTemperatureHigh']);

        // pH
        $phVal = $latestTelemetry?->ph;
        $phValue = $phVal !== null ? number_format((float) $phVal, 1) : '--';
        $phStatusLabel = $this->thresholdService->resolveStatus($phVal !== null ? (float) $phVal : null, $thresholds['phLow'], $thresholds['phHigh']);
        $phStatusType = $this->thresholdService->resolveStatusType($phVal !== null ? (float) $phVal : null, $thresholds['phLow'], $thresholds['phHigh']);

        // EC
        $ecVal = $latestTelemetry?->ec;
        $ecValue = $ecVal !== null ? number_format((float) $ecVal, 1) : '--';
        $ecStatusLabel = $this->thresholdService->resolveStatus($ecVal !== null ? (float) $ecVal : null, $thresholds['ecLow'], $thresholds['ecHigh']);
        $ecStatusType = $this->thresholdService->resolveStatusType($ecVal !== null ? (float) $ecVal : null, $thresholds['ecLow'], $thresholds['ecHigh']);

        // Water Level
        $wLvlVal = $latestTelemetry?->water_level;
        if ($wLvlVal !== null) {
            $normalizedWl = $this->telemetryService->normalizeWaterLevelValue($wLvlVal);
            $waterLevelValue = $normalizedWl['display'];
            $waterLevelStatusLabel = $this->thresholdService->resolveStatus($normalizedWl['value'], $thresholds['waterLevelLow'], $thresholds['waterLevelHigh'], 'LOW', 'FULL');
            $waterLevelStatusType = $this->thresholdService->resolveStatusType($normalizedWl['value'], $thresholds['waterLevelLow'], $thresholds['waterLevelHigh']);
        } else {
            $waterLevelValue = '--';
            $waterLevelStatusLabel = 'Waiting';
            $waterLevelStatusType = 'standby';
        }

        // Water Flow
        $wFlowVal = $latestTelemetry?->water_flow;
        $waterFlowValue = $wFlowVal !== null ? number_format((float) $wFlowVal, 1) : '--';
        $waterFlowStatusLabel = $wFlowVal !== null ? 'ONLINE' : 'OFFLINE';
        $waterFlowStatusType = $wFlowVal !== null ? 'online' : 'danger';

        // Chart Data & Metrics
        $telemetryOverviewSeries = $includeCharts ? $this->telemetryService->buildTelemetryOverviewSeries($telemetryHistory) : [];
        $telemetryOverviewCategories = $includeCharts ? $this->telemetryService->buildTelemetryOverviewCategories($telemetryHistory) : [];
        $analyticsSeries = $includeCharts ? $this->telemetryService->buildAnalyticsSeries($telemetryHistory) : [];
        $analyticsCategories = $includeCharts ? $this->telemetryService->buildAnalyticsCategories($telemetryHistory) : [];
        $telemetryChartReadings = $includeCharts ? $this->telemetryService->serializeTelemetryReadings($telemetryHistory) : [];
        $telemetryKpis = $this->telemetryService->serializeTelemetryKpis($latestTelemetry);

        $hasChartTelemetry = $includeCharts && collect(array_merge($telemetryOverviewSeries, $analyticsSeries))
            ->contains(fn (array $series): bool => ! empty($series['data']));

        $alerts = $device ? $this->alertService->getActiveAlertsForDevice($device, $alertSeverityFilter) : [];
        $monitoringSensors = $this->telemetryService->buildMonitoringSensors($latestTelemetry, $thresholds);
        $monitoringSensorsBadgeLabel = $monitoringSensors === [] ? 'Waiting for sensor data...' : count($monitoringSensors).' Sensors';

        $deviceCards = $this->deviceStatusService->buildDeviceCards($devices);
        $actuatorCards = $this->deviceStatusService->buildActuatorCards($latestTelemetry);
        $pendingCommandCount = $device ? $this->deviceCommandService->getPendingCommandCount($device) : 0;
        $alertBadgeLabel = $latestTelemetry === null
            ? 'Waiting'
            : ($alerts === [] ? '0 Active' : count($alerts).' Active');

        return array_merge($thresholds, [
            'device' => $device,
            'deviceStatusLabel' => $deviceStatusLabel,
            'deviceStatusType' => $deviceStatusType,
            'bannerStatusLabel' => $bannerStatusLabel,
            'lastUpdatedLabel' => $lastUpdatedLabel,
            'deviceNameLabel' => $deviceNameLabel,
            'deviceIdLabel' => $deviceIdLabel,
            'firmwareLabel' => $firmwareLabel,
            'localIpLabel' => $localIpLabel,
            'wifiRssiLabel' => $wifiRssiLabel,
            'lastSeenLabel' => $lastSeenLabel,
            'systemUptimeLabel' => $systemUptimeLabel,
            'batteryStatusLabel' => $batteryStatusLabel,

            'airTempValue' => $temperatureValue,
            'temperatureValue' => $temperatureValue,
            'temperatureStatusLabel' => $temperatureStatusLabel,
            'temperatureStatusType' => $temperatureStatusType,
            'temperatureTrendText' => $trendText,

            'airHumidityValue' => $humidityValue,
            'humidityValue' => $humidityValue,
            'humidityStatusLabel' => $humidityStatusLabel,
            'humidityStatusType' => $humidityStatusType,
            'humidityTrendText' => $trendText,

            'waterTempValue' => $waterTemperatureValue,
            'waterTemperatureValue' => $waterTemperatureValue,
            'waterTemperatureStatusLabel' => $waterTemperatureStatusLabel,
            'waterTemperatureStatusType' => $waterTemperatureStatusType,
            'waterTemperatureTrendText' => $trendText,

            'waterPhValue' => $phValue,
            'phValue' => $phValue,
            'phStatusLabel' => $phStatusLabel,
            'phStatusType' => $phStatusType,
            'phTrendText' => $trendText,

            'nutrientEcValue' => $ecValue,
            'ecValue' => $ecValue,
            'ecStatusLabel' => $ecStatusLabel,
            'ecStatusType' => $ecStatusType,
            'ecTrendText' => $trendText,

            'waterLevelValue' => $waterLevelValue,
            'waterLevelStatusLabel' => $waterLevelStatusLabel,
            'waterLevelStatusType' => $waterLevelStatusType,
            'waterLevelTrendText' => $trendText,

            'waterFlowValue' => $waterFlowValue,
            'waterFlowStatusLabel' => $waterFlowStatusLabel,
            'waterFlowStatusType' => $waterFlowStatusType,
            'waterFlowTrendText' => $trendText,

            'temperatureLowThreshold' => $thresholds['temperatureLow'],
            'temperatureHighThreshold' => $thresholds['temperatureHigh'],
            'humidityLowThreshold' => $thresholds['humidityLow'],
            'humidityHighThreshold' => $thresholds['humidityHigh'],
            'waterTemperatureLowThreshold' => $thresholds['waterTemperatureLow'],
            'waterTemperatureHighThreshold' => $thresholds['waterTemperatureHigh'],
            'phLowThreshold' => $thresholds['phLow'],
            'phHighThreshold' => $thresholds['phHigh'],
            'ecLowThreshold' => $thresholds['ecLow'],
            'ecHighThreshold' => $thresholds['ecHigh'],
            'waterLevelLowThreshold' => $thresholds['waterLevelLow'],
            'waterLevelHighThreshold' => $thresholds['waterLevelHigh'],
            'waterFlowLowThreshold' => $thresholds['waterFlowLow'],
            'waterFlowHighThreshold' => $thresholds['waterFlowHigh'],

            'telemetryKpis' => $telemetryKpis,
            'telemetryOverviewSeries' => $telemetryOverviewSeries,
            'telemetryOverviewCategories' => $telemetryOverviewCategories,
            'analyticsSeries' => $analyticsSeries,
            'analyticsCategories' => $analyticsCategories,
            'telemetryChartReadings' => $telemetryChartReadings,
            'hasChartTelemetry' => $hasChartTelemetry,

            'alerts' => $alerts,
            'alertBadgeLabel' => $alertBadgeLabel,
            'pendingCommandCount' => $pendingCommandCount,
            'monitoringSensors' => $monitoringSensors,
            'monitoringSensorsBadgeLabel' => $monitoringSensorsBadgeLabel,
            'deviceCards' => $deviceCards,
            'actuatorCards' => $actuatorCards,
        ]);
    }

    protected function getTelemetryHistoryForDashboard(?Device $device): Collection
    {
        if ($device === null) {
            return collect();
        }

        $cacheKey = 'dashboard.telemetry-history.'.$device->id.'.live';

        $cachedPayload = Cache::remember($cacheKey, 5, function () use ($device): array {
            return $this->getTelemetryHistory($device, 100)
                ->map(fn (Telemetry $telemetry): array => $telemetry->getAttributes())
                ->values()
                ->all();
        });

        if (! is_array($cachedPayload)) {
            Cache::forget($cacheKey);

            $cachedPayload = $this->getTelemetryHistory($device, 100)
                ->map(fn (Telemetry $telemetry): array => $telemetry->getAttributes())
                ->values()
                ->all();

            Cache::put($cacheKey, $cachedPayload, 5);
        }

        return Telemetry::hydrate($cachedPayload);
    }

    protected function getLatestTelemetryForDashboard(?Device $device): ?Telemetry
    {
        if ($device === null) {
            return null;
        }

        return $device->latestTelemetry;
    }

    protected function getTelemetryHistory(Device $device, int $limit): Collection
    {
        return $this->telemetryService->getTelemetryHistory($device, $limit);
    }

    protected function resolveDashboardDevice(Collection $devices, ?\App\Models\Greenhouse $greenhouse = null): ?Device
    {
        if ($greenhouse !== null) {
            $ghDevice = $greenhouse->devices()->with('latestTelemetry')->latest('last_seen_at')->first()
                ?? $greenhouse->device;
            if ($ghDevice instanceof Device) {
                return $ghDevice;
            }
        }

        $user = auth()->user();
        if ($user && $user->isFarmer() && ! $user->isSuperAdmin()) {
            $farmerGh = $user->greenhouses()->with('devices.latestTelemetry')->first() ?? $user->greenhouse;
            if ($farmerGh) {
                $farmerDevice = $farmerGh->devices()->with('latestTelemetry')->latest('last_seen_at')->first()
                    ?? $farmerGh->device;
                if ($farmerDevice instanceof Device) {
                    return $farmerDevice;
                }
            }
        }

        $configuredDeviceId = (int) config('leaf.dashboard.device_db_id', 358);

        if ($configuredDeviceId > 0) {
            $configuredDevice = $devices->firstWhere('id', $configuredDeviceId)
                ?? Device::query()
                    ->with('latestTelemetry')
                    ->find($configuredDeviceId);

            if ($configuredDevice instanceof Device) {
                return $configuredDevice;
            }
        }

        return $devices->first();
    }

}
