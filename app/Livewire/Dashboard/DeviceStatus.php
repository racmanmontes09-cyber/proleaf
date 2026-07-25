<?php

namespace App\Livewire\Dashboard;

use App\Models\Device;
use App\Services\AlertService;
use App\Services\DashboardService;
use App\Services\DeviceCommandService;
use Livewire\Component;

class DeviceStatus extends Component
{
    public const ALERT_SEVERITY_FILTER_ALL = 'all';

    public ?Device $device = null;

    public string $deviceStatusLabel = 'Waiting for device...';

    public string $alertBadgeLabel = 'Waiting';

    public string $deviceStatusType = 'online';

    public string $bannerStatusLabel = 'ESP32 Hardware Node: Waiting for device...';

    public string $lastUpdatedLabel = 'Last Updated: Waiting for device...';

    public string $deviceNameLabel = 'Waiting for device...';

    public string $deviceIdLabel = 'Waiting for device ID...';

    public string $firmwareLabel = 'Waiting for firmware...';

    public string $localIpLabel = 'Waiting for device...';

    public string $wifiRssiLabel = 'Waiting for hardware...';

    public string $lastSeenLabel = 'Waiting for device...';

    public string $systemUptimeLabel = 'Waiting for hardware...';

    public string $batteryStatusLabel = 'Pending hardware integration';

    public string $airTempValue = '--';

    public string $airHumidityValue = '--';

    public string $humidityValue = '--';

    public string $humidityStatusLabel = 'Waiting';

    public string $humidityStatusType = 'standby';

    public string $humidityTrendText = 'Waiting for sensor data...';

    public ?float $humidityLowThreshold = null;

    public ?float $humidityHighThreshold = null;

    public string $humidityTargetLabel = 'Not available';

    public string $waterTempValue = '--';

    public string $waterTemperatureValue = '--';

    public string $waterTemperatureStatusLabel = 'Waiting';

    public string $waterTemperatureStatusType = 'standby';

    public string $waterTemperatureTrendText = 'Waiting for sensor data...';

    public ?float $waterTemperatureLowThreshold = null;

    public ?float $waterTemperatureHighThreshold = null;

    public string $waterTemperatureTargetLabel = 'Not available';

    public string $waterPhValue = '--';

    public string $phValue = '--';

    public string $phStatusLabel = 'Waiting';

    public string $phStatusType = 'standby';

    public string $phTrendText = 'Waiting for sensor data...';

    public ?float $phLowThreshold = null;

    public ?float $phHighThreshold = null;

    public string $phTargetLabel = 'Not available';

    public string $nutrientEcValue = '--';

    public string $ecValue = '--';

    public string $ecStatusLabel = 'Waiting';

    public string $ecStatusType = 'standby';

    public string $ecTrendText = 'Waiting for sensor data...';

    public ?float $ecLowThreshold = null;

    public ?float $ecHighThreshold = null;

    public string $ecTargetLabel = 'Not available';

    public string $temperatureValue = '--';

    public string $temperatureStatusLabel = 'Waiting';

    public string $temperatureStatusType = 'standby';

    public string $temperatureTrendText = 'Waiting for sensor data...';

    public ?float $temperatureLowThreshold = null;

    public ?float $temperatureHighThreshold = null;

    public string $temperatureTargetLabel = 'Not available';

    public string $waterLevelValue = '--';

    public string $waterLevelStatusLabel = 'Waiting';

    public string $waterLevelStatusType = 'standby';

    public string $waterLevelTrendText = 'Waiting for sensor data...';

    public ?float $waterLevelLowThreshold = null;

    public ?float $waterLevelHighThreshold = null;

    public string $waterLevelTargetLabel = 'Not available';

    public string $waterFlowValue = '--';

    public string $waterFlowStatusLabel = 'Waiting';

    public string $waterFlowStatusType = 'standby';

    public string $waterFlowTrendText = 'Waiting for sensor data...';

    public ?float $waterFlowLowThreshold = null;

    public ?float $waterFlowHighThreshold = null;

    public string $waterFlowTargetLabel = 'Not available';

    public array $telemetryOverviewSeries = [];

    public array $telemetryOverviewCategories = [];

    public array $analyticsSeries = [];

    public array $analyticsCategories = [];

    public array $yieldSeries = [];

    public array $yieldLabels = [];

    public bool $hasChartTelemetry = false;

    public bool $hasYieldData = false;

    public string $monitoringSensorsBadgeLabel = 'Waiting for sensor data...';

    public array $alerts = [];

    public array $monitoringSensors = [];

    public array $deviceCards = [];

    public array $actuatorCards = [];

    public int $pendingCommandCount = 0;

    public string $commandStatusMessage = '';

    public string $alertSeverityFilter = self::ALERT_SEVERITY_FILTER_ALL;

    public function render(DashboardService $dashboardService, AlertService $alertService)
    {
        $this->refreshDashboard($dashboardService, false);

        return view('livewire.dashboard.device-status', [
            'device' => $this->device,
            'telemetryAlerts' => $alertService->filteredTelemetryAlerts($this->alerts, $this->alertSeverityFilter),
        ]);
    }

    public function refreshDashboard(DashboardService $dashboardService, bool $dispatchChartUpdate = true): void
    {
        $data = $dashboardService->getDashboardData($this->alertSeverityFilter);

        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        if ($dispatchChartUpdate) {
            $this->dispatch('dashboard-chart-data-updated',
                telemetryOverviewSeries: $this->telemetryOverviewSeries,
                telemetryOverviewCategories: $this->telemetryOverviewCategories,
                analyticsSeries: $this->analyticsSeries,
                analyticsCategories: $this->analyticsCategories,
                yieldSeries: $this->yieldSeries,
                yieldLabels: $this->yieldLabels,
                hasChartTelemetry: $this->hasChartTelemetry,
                hasYieldData: $this->hasYieldData,
            );
        }
    }

    public function acknowledgeAlert(int|string $alertKey, AlertService $alertService): void
    {
        if ($this->device === null) {
            return;
        }

        if (! auth()->user()?->canPerform('alerts.acknowledge')) {
            $this->commandStatusMessage = 'Forbidden.';
            return;
        }

        $alertService->acknowledgeAlertByKey($alertKey, auth()->id());
        $this->alerts = $alertService->getActiveAlertsForDevice($this->device, $this->alertSeverityFilter);
    }

    public function queueDeviceCommand(string $command): void
    {
        if ($this->device === null) {
            return;
        }
        if (! auth()->user()?->canPerform('commands.issue')) {
            $this->commandStatusMessage = 'Forbidden.';
            return;
        }
        $commandService = app(DeviceCommandService::class);
        $title = match ($command) {
            'restart_node' => 'Restart Node',
            'configure_pins' => 'Configure Pins',
            default => ucwords(str_replace('_', ' ', $command)),
        };

        $commandService->queueCommand(
            $this->device,
            $command,
            [],
            $title,
            3,
            now()->addHour(),
            ['source' => 'dashboard']
        );

        $this->commandStatusMessage = "Queued command: {$title}";
        $this->refreshDashboard(app(DashboardService::class));
    }

    public function filteredTelemetryAlerts(): array
    {
        return app(AlertService::class)->filteredTelemetryAlerts($this->alerts, $this->alertSeverityFilter);
    }
}