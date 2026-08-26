<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Device;
use App\Models\Telemetry;
use App\Repositories\AlertRepository;
use Illuminate\Support\Carbon;

class AlertService
{
    public const ALERT_SEVERITY_FILTER_ALL = 'all';

    public const ALERT_SEVERITIES = [
        'info',
        'warning',
        'critical',
    ];

    protected array $activeSensors = [
        'air_temperature',
        'humidity',
        'water_temperature',
        'ph',
        'ec',
        'water_flow',
        'water_level',
    ];

    public function __construct(
        protected ThresholdService $thresholdService,
        protected AlertRepository $alertRepository
    ) {}

    public function evaluateTelemetry(Device $device, Telemetry $telemetry): array
    {
        $thresholds = $this->thresholdService->getAllThresholds();
        $preloaded = $this->alertRepository->preloadActiveAlertsBySensor($device);
        $sensorsToResolve = [];

        foreach ($this->activeSensors as $sensor) {
            $value = $telemetry->{$sensor};
            $metadata = ['icon' => $this->getSensorIcon($sensor)];

            switch ($sensor) {
                case 'air_temperature':
                    $this->evaluateThresholdAlert(
                        $device,
                        $telemetry,
                        $sensor,
                        $value,
                        $thresholds['temperatureLow'],
                        $thresholds['temperatureHigh'],
                        'Air Temperature',
                        'Air temperature is %s relative to the configured threshold.',
                        'thermometer',
                        $metadata,
                        'LOW',
                        'HIGH',
                        $preloaded,
                        $sensorsToResolve
                    );
                    break;
                case 'humidity':
                    $this->evaluateThresholdAlert(
                        $device,
                        $telemetry,
                        $sensor,
                        $value,
                        $thresholds['humidityLow'],
                        $thresholds['humidityHigh'],
                        'Humidity',
                        'Humidity is %s relative to the configured threshold.',
                        'droplet',
                        $metadata,
                        'LOW',
                        'HIGH',
                        $preloaded,
                        $sensorsToResolve
                    );
                    break;
                case 'water_temperature':
                    $this->evaluateThresholdAlert(
                        $device,
                        $telemetry,
                        $sensor,
                        $value,
                        $thresholds['waterTemperatureLow'],
                        $thresholds['waterTemperatureHigh'],
                        'Water Temperature',
                        'Water temperature is %s relative to the configured threshold.',
                        'thermometer',
                        $metadata,
                        'LOW',
                        'HIGH',
                        $preloaded,
                        $sensorsToResolve
                    );
                    break;
                case 'ph':
                    $this->evaluateThresholdAlert(
                        $device,
                        $telemetry,
                        $sensor,
                        $value,
                        $thresholds['phLow'],
                        $thresholds['phHigh'],
                        'Water pH',
                        'Water pH is %s relative to the configured threshold.',
                        'beaker',
                        $metadata,
                        'LOW',
                        'HIGH',
                        $preloaded,
                        $sensorsToResolve
                    );
                    break;
                case 'ec':
                    $this->evaluateThresholdAlert(
                        $device,
                        $telemetry,
                        $sensor,
                        $value,
                        $thresholds['ecLow'],
                        $thresholds['ecHigh'],
                        'EC',
                        'EC is %s relative to the configured threshold.',
                        'sparkles',
                        $metadata,
                        'LOW',
                        'HIGH',
                        $preloaded,
                        $sensorsToResolve
                    );
                    break;
                case 'water_flow':
                    $this->evaluateThresholdAlert(
                        $device,
                        $telemetry,
                        $sensor,
                        $value,
                        $thresholds['waterFlowLow'],
                        $thresholds['waterFlowHigh'],
                        'Water Flow',
                        'Water flow is currently %s.',
                        'waves',
                        $metadata,
                        'LOW FLOW',
                        'HIGH FLOW',
                        $preloaded,
                        $sensorsToResolve
                    );
                    break;
                case 'water_level':
                    $normalizedValue = $this->normalizeWaterLevelValue($value);
                    $this->evaluateThresholdAlert(
                        $device,
                        $telemetry,
                        $sensor,
                        $normalizedValue !== null ? $normalizedValue['value'] : null,
                        $thresholds['waterLevelLow'],
                        $thresholds['waterLevelHigh'],
                        'Water Level',
                        'Water level is currently %s.',
                        'droplet',
                        $metadata,
                        'LOW',
                        'FULL',
                        $preloaded,
                        $sensorsToResolve
                    );
                    break;
            }
        }

        $this->evaluateOfflineAlert($device, $telemetry, $preloaded, $sensorsToResolve);

        if ($sensorsToResolve !== []) {
            $this->alertRepository->batchResolve($sensorsToResolve);
        }

        return $this->getActiveAlertsForDevice($device);
    }

    public function getActiveAlertsForDevice(Device $device, string $severityFilter = self::ALERT_SEVERITY_FILTER_ALL): array
    {
        $alerts = $this->alertRepository->activeAlertsForDevice($device)->map(fn (Alert $alert) => $alert->toDashboardArray())->all();

        return $this->filteredTelemetryAlerts($alerts, $severityFilter);
    }

    public function getActiveAlertCount(Device $device): int
    {
        return $this->alertRepository->activeAlertCountForDevice($device);
    }

    public function acknowledgeAlertByKey(string|int $alertKey, ?int $userId = null): ?array
    {
        $alert = Alert::query()->find($alertKey);

        if ($alert === null) {
            return null;
        }

        $alert = $this->alertRepository->acknowledge($alert, $userId);

        return $alert->toDashboardArray();
    }

    protected function evaluateThresholdAlert(
        Device $device,
        Telemetry $telemetry,
        string $sensor,
        mixed $value,
        ?float $low,
        ?float $high,
        string $baseTitle,
        string $messageTemplate,
        string $icon,
        array $metadata = [],
        string $lowLabel = 'LOW',
        string $highLabel = 'HIGH',
        array $preloaded = [],
        array &$sensorsToResolve = []
    ): void {
        if ($value === null) {
            $sensorsToResolve = array_merge($sensorsToResolve, $preloaded[$sensor] ?? []);
            return;
        }

        $status = $this->thresholdService->resolveStatus($value, $low, $high, $lowLabel, $highLabel);

        if ($this->thresholdService->shouldPushThresholdAlert($status)) {
            $title = $baseTitle.' '.$status;
            $message = sprintf($messageTemplate, strtolower($status));
            $threshold = $this->formatThreshold($low, $high, $lowLabel, $highLabel);

            if ($this->alertRepository->findInPreloaded($preloaded, $sensor, $title) === null) {
                $this->alertRepository->create([
                    'device_id' => $device->id,
                    'telemetry_id' => $telemetry->id,
                    'title' => $title,
                    'message' => $message,
                    'sensor' => $sensor,
                    'severity' => 'warning',
                    'status' => 'active',
                    'value' => $value,
                    'threshold' => $threshold,
                    'metadata' => array_merge($metadata, ['icon' => $icon]),
                ]);
            }

            return;
        }

        $sensorsToResolve = array_merge($sensorsToResolve, $preloaded[$sensor] ?? []);
    }

    protected function evaluateOfflineAlert(Device $device, Telemetry $telemetry, array $preloaded = [], array &$sensorsToResolve = []): void
    {
        if (! $device->is_online) {
            if ($this->alertRepository->findInPreloaded($preloaded, 'device_offline', 'Greenhouse Device Offline') === null
                && $this->alertRepository->findInPreloaded($preloaded, 'device_offline', 'ESP32 Offline') === null) {
                $this->triggerOfflineAlert($device, $telemetry);
            }

            return;
        }

        $offlineAlerts = $preloaded['device_offline'] ?? [];
        if (! empty($offlineAlerts)) {
            $this->triggerRecoveryNotification($device);
        }

        $sensorsToResolve = array_merge($sensorsToResolve, $offlineAlerts);
    }

    /**
     * Create an offline alert for a device and dispatch an email to Super Admins.
     */
    public function triggerOfflineAlert(Device $device, ?Telemetry $telemetry = null): ?Alert
    {
        $existing = $this->alertRepository->findActiveOfflineAlert($device);
        if ($existing !== null) {
            return null;
        }

        $greenhouse = $device->greenhouse;
        $farmer = $greenhouse?->farmer;
        $greenhouseName = $greenhouse?->name ?? 'Default Greenhouse';
        $farmerName = $farmer?->name ?? 'Unassigned';

        $alert = $this->alertRepository->create([
            'device_id' => $device->id,
            'greenhouse_id' => $greenhouse?->id,
            'telemetry_id' => $telemetry?->id,
            'title' => 'Greenhouse Device Offline',
            'message' => "Greenhouse '{$greenhouseName}' device ({$device->device_id}) is offline. Assigned farmer: {$farmerName}.",
            'sensor' => 'device_offline',
            'severity' => 'critical',
            'status' => 'active',
            'value' => null,
            'threshold' => null,
            'metadata' => [
                'icon' => 'signal',
                'greenhouse_id' => $greenhouse?->id,
                'greenhouse' => $greenhouseName,
                'farmer' => $farmerName,
                'device' => $device->device_id,
                'last_seen' => $device->last_seen_at?->toIso8601String(),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);

        $this->sendOfflineEmail($device, $alert);

        return $alert;
    }

    /**
     * Create a recovery notification when an offline device reconnects.
     */
    public function triggerRecoveryNotification(Device $device): ?Alert
    {
        $this->alertRepository->resolveOfflineAlerts($device);

        $greenhouse = $device->greenhouse;
        $farmer = $greenhouse?->farmer;
        $greenhouseName = $greenhouse?->name ?? 'Default Greenhouse';
        $farmerName = $farmer?->name ?? 'Unassigned';

        return $this->alertRepository->create([
            'device_id' => $device->id,
            'greenhouse_id' => $greenhouse?->id,
            'title' => 'Greenhouse Device Reconnected',
            'message' => "Greenhouse '{$greenhouseName}' device ({$device->device_id}) is back online.",
            'sensor' => 'device_online',
            'severity' => 'info',
            'status' => 'active',
            'value' => null,
            'threshold' => null,
            'metadata' => [
                'icon' => 'check-circle',
                'recovery' => true,
                'greenhouse_id' => $greenhouse?->id,
                'greenhouse' => $greenhouseName,
                'farmer' => $farmerName,
                'device' => $device->device_id,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Send email to Super Admins notifying that a greenhouse is offline.
     */
    public function sendOfflineEmail(Device $device, ?Alert $alert = null): void
    {
        try {
            $greenhouse = $device->greenhouse ?? new \App\Models\Greenhouse([
                'name' => 'Default Greenhouse',
                'location' => 'Zone 1',
            ]);

            $farmerName = $greenhouse->farmer?->name ?? 'Unassigned';
            $lastSeen = $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Never';

            $superAdmins = \App\Models\User::query()
                ->whereHas('roles', fn ($q) => $q->where('slug', config('rbac.super_admin_role', 'super-admin')))
                ->get();

            if ($superAdmins->isNotEmpty()) {
                foreach ($superAdmins as $admin) {
                    \Illuminate\Support\Facades\Mail::to($admin->email)->send(
                        new \App\Mail\GreenhouseOfflineNotification(
                            greenhouse: $greenhouse,
                            device: $device,
                            farmerName: $farmerName,
                            lastSeen: $lastSeen
                        )
                    );
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed sending greenhouse offline email notification: '.$e->getMessage());
        }
    }

    protected function resolveAlertForSensor(Device $device, string $sensor): void
    {
        $alerts = Alert::query()
            ->forDevice($device)
            ->active()
            ->where('sensor', $sensor)
            ->get();

        foreach ($alerts as $alert) {
            $this->alertRepository->resolve($alert);
        }
    }

    protected function getSensorIcon(string $sensor): string
    {
        return match ($sensor) {
            'air_temperature', 'water_temperature' => 'thermometer',
            'humidity' => 'droplet',
            'ph' => 'beaker',
            'ec' => 'sparkles',
            'water_flow' => 'waves',
            'water_level' => 'droplet',
            default => 'alert-circle',
        };
    }

    protected function normalizeWaterLevelValue(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $numericValue = (float) $value;

        if ($numericValue >= 0 && $numericValue <= 1) {
            return [
                'value' => $numericValue * 100,
                'display' => number_format($numericValue * 100, 0),
            ];
        }

        return [
            'value' => $numericValue,
            'display' => number_format($numericValue, 0),
        ];
    }

    protected function formatThreshold(?float $low, ?float $high, string $lowLabel, string $highLabel): ?string
    {
        if ($low === null || $high === null) {
            return null;
        }

        return sprintf('%s-%s', $this->thresholdService->formatThresholdValue($low), $this->thresholdService->formatThresholdValue($high));
    }

    /**
     * Filter alerts by severity ('all', 'info', 'warning', 'critical').
     */
    public function filteredTelemetryAlerts(array $alerts, string $severityFilter): array
    {
        $filter = $this->normalizeAlertSeverityFilter($severityFilter);

        return array_values(array_filter(
            array_map(
                fn (mixed $alert, int|string $index): array => $this->normalizeTelemetryAlert($alert, $index),
                $alerts,
                array_keys($alerts)
            ),
            fn (array $alert): bool => $filter === self::ALERT_SEVERITY_FILTER_ALL || $alert['severity'] === $filter
        ));
    }

    public function acknowledgeAlert(array $alerts, int|string $alertKey): array
    {
        foreach ($alerts as $index => $alert) {
            $key = data_get($alert, 'key', data_get($alert, 'id', data_get($alert, 'uuid', $index)));

            if ((string) $key !== (string) $alertKey) {
                continue;
            }

            if (is_array($alert)) {
                $alerts[$index]['acknowledged'] = true;

                return $alerts;
            }

            if (is_object($alert)) {
                $alert->acknowledged = true;
                $alerts[$index] = $alert;
            }

            return $alerts;
        }

        return $alerts;
    }

    public function normalizeTelemetryAlert(mixed $alert, int|string $index): array
    {
        $severity = $this->normalizeAlertSeverity(data_get($alert, 'severity'));
        $timestampValue = data_get($alert, 'timestamp')
            ?? data_get($alert, 'time')
            ?? data_get($alert, 'created_at')
            ?? data_get($alert, 'updated_at');
        $timestamp = $this->normalizeAlertTimestamp($timestampValue);

        return [
            'key' => (string) data_get($alert, 'id', data_get($alert, 'key', data_get($alert, 'uuid', $index))),
            'title' => (string) (data_get($alert, 'title') ?: 'System Alert'),
            'message' => (string) (data_get($alert, 'message') ?? ''),
            'severity' => $severity,
            'card_severity' => $severity === 'critical' ? 'danger' : $severity,
            'timestamp' => $timestamp?->toIso8601String(),
            'time' => $timestamp?->diffForHumans() ?? 'Not available',
            'icon' => data_get($alert, 'icon'),
            'sensor' => data_get($alert, 'sensor'),
            'acknowledged' => (bool) data_get($alert, 'acknowledged', data_get($alert, 'read', false)),
        ];
    }

    /**
     * Normalize alert severity string.
     */
    public function normalizeAlertSeverity(mixed $severity): string
    {
        return match (strtolower(trim((string) $severity))) {
            'warning', 'warn' => 'warning',
            'critical', 'danger', 'error' => 'critical',
            default => 'info',
        };
    }

    /**
     * Normalize alert severity filter input.
     */
    public function normalizeAlertSeverityFilter(string $severity): string
    {
        $severity = strtolower(trim($severity));

        if ($severity === '' || $severity === self::ALERT_SEVERITY_FILTER_ALL) {
            return self::ALERT_SEVERITY_FILTER_ALL;
        }

        if (in_array($severity, self::ALERT_SEVERITIES, true)) {
            return $severity;
        }

        return match ($severity) {
            'warn' => 'warning',
            'danger', 'error' => 'critical',
            default => self::ALERT_SEVERITY_FILTER_ALL,
        };
    }

    /**
     * Normalize alert timestamp to Carbon instance.
     */
    public function normalizeAlertTimestamp(mixed $timestamp): ?Carbon
    {
        if ($timestamp instanceof \DateTimeInterface) {
            return Carbon::instance($timestamp);
        }

        if ($timestamp === null || $timestamp === '') {
            return null;
        }

        try {
            return Carbon::parse($timestamp);
        } catch (\Throwable) {
            return null;
        }
    }
}
