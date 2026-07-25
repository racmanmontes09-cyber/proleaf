<?php

namespace App\Services;
use App\Services\DeviceCommandService;

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
    public function getDashboardData(string $alertSeverityFilter = 'all'): array
    {
        $thresholds = $this->thresholdService->getAllThresholds();
        $devices = $this->telemetryService->getDevicesWithTelemetries(2);

        $device = $devices->first();
        $latestTelemetry = $device?->latestTelemetry;

        // Bounded query for telemetry history (max 100 entries, index-ordered)
        $telemetryHistory = $this->telemetryService->getTelemetryHistory($device, 100);

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
        $waterFlowStatusLabel = $this->thresholdService->resolveStatus($wFlowVal !== null ? (float) $wFlowVal : null, $thresholds['waterFlowLow'], $thresholds['waterFlowHigh'], 'LOW FLOW', 'HIGH FLOW');
        $waterFlowStatusType = $this->thresholdService->resolveStatusType($wFlowVal !== null ? (float) $wFlowVal : null, $thresholds['waterFlowLow'], $thresholds['waterFlowHigh']);

        // Chart Data & Metrics
        $telemetryOverviewSeries = $this->telemetryService->buildTelemetryOverviewSeries($telemetryHistory);
        $telemetryOverviewCategories = $this->telemetryService->buildTelemetryOverviewCategories($telemetryHistory);
        $analyticsSeries = $this->telemetryService->buildAnalyticsSeries($telemetryHistory);
        $analyticsCategories = $this->telemetryService->buildAnalyticsCategories($telemetryHistory);

        $hasChartTelemetry = collect(array_merge($telemetryOverviewSeries, $analyticsSeries))
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

            'telemetryOverviewSeries' => $telemetryOverviewSeries,
            'telemetryOverviewCategories' => $telemetryOverviewCategories,
            'analyticsSeries' => $analyticsSeries,
            'analyticsCategories' => $analyticsCategories,
            'yieldSeries' => [],
            'yieldLabels' => [],
            'hasChartTelemetry' => $hasChartTelemetry,
            'hasYieldData' => false,

            'alerts' => $alerts,
            'alertBadgeLabel' => $alertBadgeLabel,
            'pendingCommandCount' => $pendingCommandCount,
            'monitoringSensors' => $monitoringSensors,
            'monitoringSensorsBadgeLabel' => $monitoringSensorsBadgeLabel,
            'deviceCards' => $deviceCards,
            'actuatorCards' => $actuatorCards,
        ]);
    }
}
