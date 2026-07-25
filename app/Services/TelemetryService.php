<?php

namespace App\Services;

use App\Events\TelemetryReceived;
use App\Models\Device;
use App\Models\Telemetry;
use App\Services\AlertService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

class TelemetryService
{
    public function __construct(
        protected ThresholdService $thresholdService,
        protected AlertService $alertService
    ) {}

    /**
     * Store telemetry payload for a device with duplicate prevention handling.
     */
    public function storeTelemetry(Device $device, array $payload): array
    {
        $startTime = microtime(true);

        try {
            $telemetry = $device->telemetries()->create($payload);
            $storeTime = microtime(true);

            $this->alertService->evaluateTelemetry($device, $telemetry);

            broadcast(new TelemetryReceived($telemetry));
            $broadcastTime = microtime(true);

            return [
                'success' => true,
                'created' => true,
                'message' => 'Telemetry stored successfully.',
                'telemetry' => $telemetry,
                'timing' => [
                    'http_to_store_ms' => round(($storeTime - $startTime) * 1000, 2),
                    'store_to_broadcast_ms' => round(($broadcastTime - $storeTime) * 1000, 2),
                    'total_backend_ms' => round(($broadcastTime - $startTime) * 1000, 2),
                ],
            ];
        } catch (QueryException $e) {
            if ($this->isDuplicateEntryException($e)) {
                return [
                    'success' => true,
                    'created' => false,
                    'message' => 'Telemetry already stored.',
                    'telemetry' => null,
                ];
            }

            throw $e;
        }
    }

    /**
     * Determine if a QueryException is caused by duplicate unique constraint.
     */
    public function isDuplicateEntryException(QueryException $e): bool
    {
        $code = (string) $e->getCode();
        $message = strtolower($e->getMessage());

        return $code === '23000'
            || str_contains($message, 'unique constraint failed')
            || str_contains($message, 'duplicate entry')
            || str_contains($message, '1062');
    }

    /**
     * Get devices required for the dashboard with eager-loaded latestTelemetry.
     * Limits the returned device set to avoid loading all devices in the dashboard path.
     */
    public function getDevicesWithTelemetries(int $limit = 2): Collection
    {
        return Device::query()
            ->latest('last_seen_at')
            ->select([
                'id',
                'device_id',
                'name',
                'firmware_version',
                'local_ip_address',
                'wifi_rssi',
                'uptime_seconds',
                'free_heap',
                'last_seen_at',
            ])
            ->with(['latestTelemetry' => function ($query) {
                $query->select([
                    'telemetries.id',
                    'telemetries.device_id',
                    'telemetries.air_temperature',
                    'telemetries.humidity',
                    'telemetries.water_temperature',
                    'telemetries.ph',
                    'telemetries.ec',
                    'telemetries.water_flow',
                    'telemetries.water_level',
                    'telemetries.measured_at',
                    'telemetries.updated_at',
                    'telemetries.created_at',
                ]);
            }])
            ->limit($limit)
            ->get();
    }

    /**
     * Get bounded historical telemetry for charts (last 24 hours, capped to limit, index-ordered).
     */
    public function getTelemetryHistory(?Device $device, int $limit = 100): Collection
    {
        if ($device === null) {
            return collect();
        }

        return $device->telemetries()
            ->select([
                'id',
                'device_id',
                'air_temperature',
                'humidity',
                'water_temperature',
                'ph',
                'ec',
                'water_flow',
                'water_level',
                'measured_at',
                'updated_at',
                'created_at',
            ])
            ->last24Hours()
            ->latestReading()
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Normalize water level value to percentage scale (0-100).
     */
    public function normalizeWaterLevelValue(mixed $value): array
    {
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

    /**
     * Build telemetry overview chart series.
     */
    public function buildTelemetryOverviewSeries(Collection $history): array
    {
        return [
            ['name' => 'Water pH', 'data' => $history->pluck('ph')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->values()->all()],
            ['name' => 'Water Temp (°C)', 'data' => $history->pluck('water_temperature')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->values()->all()],
            ['name' => 'Nutrient EC (mS)', 'data' => $history->pluck('ec')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->values()->all()],
        ];
    }

    /**
     * Build telemetry overview chart time categories.
     */
    public function buildTelemetryOverviewCategories(Collection $history): array
    {
        return $history->map(fn (Telemetry $t): string => ($t->measured_at ?? $t->updated_at ?? $t->created_at)?->format('H:i') ?? '')->values()->all();
    }

    /**
     * Build analytics series.
     */
    public function buildAnalyticsSeries(Collection $history): array
    {
        return [
            ['name' => 'Air Temp (°C)', 'type' => 'column', 'data' => $history->pluck('air_temperature')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->values()->all()],
            ['name' => 'Humidity (%)', 'type' => 'line', 'data' => $history->pluck('humidity')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->values()->all()],
            ['name' => 'Water Flow (L/min)', 'type' => 'line', 'data' => $history->pluck('water_flow')->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v)->values()->all()],
        ];
    }

    /**
     * Build analytics date categories.
     */
    public function buildAnalyticsCategories(Collection $history): array
    {
        return $history->map(fn (Telemetry $t): string => ($t->measured_at ?? $t->updated_at ?? $t->created_at)?->format('M d') ?? '')->values()->all();
    }

    /**
     * Build monitoring sensors data cards.
     */
    public function buildMonitoringSensors(?Telemetry $telemetry, array $thresholds): array
    {
        if ($telemetry === null) {
            return [];
        }

        $timestamp = $telemetry->measured_at ?? $telemetry->updated_at ?? $telemetry->created_at;
        $updatedAt = $timestamp ? $timestamp->diffForHumans() : 'Waiting for sensor data...';
        $waterLevel = $telemetry->water_level !== null ? $this->normalizeWaterLevelValue($telemetry->water_level) : null;

        $airTempStatus = $telemetry->air_temperature !== null
            ? $this->thresholdService->resolveStatus((float) $telemetry->air_temperature, $thresholds['temperatureLow'], $thresholds['temperatureHigh'])
            : 'Waiting';
        $airTempType = $telemetry->air_temperature !== null
            ? $this->thresholdService->resolveStatusType((float) $telemetry->air_temperature, $thresholds['temperatureLow'], $thresholds['temperatureHigh'])
            : 'standby';

        $humidityStatus = $telemetry->humidity !== null
            ? $this->thresholdService->resolveStatus((float) $telemetry->humidity, $thresholds['humidityLow'], $thresholds['humidityHigh'])
            : 'Waiting';
        $humidityType = $telemetry->humidity !== null
            ? $this->thresholdService->resolveStatusType((float) $telemetry->humidity, $thresholds['humidityLow'], $thresholds['humidityHigh'])
            : 'standby';

        $waterTempStatus = $telemetry->water_temperature !== null
            ? $this->thresholdService->resolveStatus((float) $telemetry->water_temperature, $thresholds['waterTemperatureLow'], $thresholds['waterTemperatureHigh'])
            : 'Waiting';
        $waterTempType = $telemetry->water_temperature !== null
            ? $this->thresholdService->resolveStatusType((float) $telemetry->water_temperature, $thresholds['waterTemperatureLow'], $thresholds['waterTemperatureHigh'])
            : 'standby';

        $phStatus = $telemetry->ph !== null
            ? $this->thresholdService->resolveStatus((float) $telemetry->ph, $thresholds['phLow'], $thresholds['phHigh'])
            : 'Waiting';
        $phType = $telemetry->ph !== null
            ? $this->thresholdService->resolveStatusType((float) $telemetry->ph, $thresholds['phLow'], $thresholds['phHigh'])
            : 'standby';

        $ecStatus = $telemetry->ec !== null
            ? $this->thresholdService->resolveStatus((float) $telemetry->ec, $thresholds['ecLow'], $thresholds['ecHigh'])
            : 'Waiting';
        $ecType = $telemetry->ec !== null
            ? $this->thresholdService->resolveStatusType((float) $telemetry->ec, $thresholds['ecLow'], $thresholds['ecHigh'])
            : 'standby';

        $waterLevelStatus = $waterLevel !== null
            ? $this->thresholdService->resolveStatus($waterLevel['value'], $thresholds['waterLevelLow'], $thresholds['waterLevelHigh'], 'LOW', 'FULL')
            : 'Waiting';
        $waterLevelType = $waterLevel !== null
            ? $this->thresholdService->resolveStatusType($waterLevel['value'], $thresholds['waterLevelLow'], $thresholds['waterLevelHigh'])
            : 'standby';

        $waterFlowStatus = $telemetry->water_flow !== null
            ? $this->thresholdService->resolveStatus((float) $telemetry->water_flow, $thresholds['waterFlowLow'], $thresholds['waterFlowHigh'], 'LOW FLOW', 'HIGH FLOW')
            : 'Waiting';
        $waterFlowType = $telemetry->water_flow !== null
            ? $this->thresholdService->resolveStatusType((float) $telemetry->water_flow, $thresholds['waterFlowLow'], $thresholds['waterFlowHigh'])
            : 'standby';

        return [
            [
                'name' => 'Ambient Air Temperature',
                'value' => $telemetry->air_temperature !== null ? number_format((float) $telemetry->air_temperature, 1) : '--',
                'unit' => '°C',
                'status' => $airTempStatus,
                'statusType' => $airTempType,
                'min' => $thresholds['temperatureLow'] !== null ? $this->thresholdService->formatThresholdValue($thresholds['temperatureLow']) : 'Not available',
                'max' => $thresholds['temperatureHigh'] !== null ? $this->thresholdService->formatThresholdValue($thresholds['temperatureHigh']) : 'Not available',
                'percentage' => $this->thresholdService->scaleTelemetryPercentage($telemetry->air_temperature !== null ? (float) $telemetry->air_temperature : null, $thresholds['temperatureLow'], $thresholds['temperatureHigh']),
                'optimalRange' => $thresholds['temperatureTargetLabel'],
                'lastCalibrated' => $updatedAt,
            ],
            [
                'name' => 'Relative Air Humidity',
                'value' => $telemetry->humidity !== null ? number_format((float) $telemetry->humidity, 0) : '--',
                'unit' => '%',
                'status' => $humidityStatus,
                'statusType' => $humidityType,
                'min' => $thresholds['humidityLow'] !== null ? $this->thresholdService->formatThresholdValue($thresholds['humidityLow']) : 'Not available',
                'max' => $thresholds['humidityHigh'] !== null ? $this->thresholdService->formatThresholdValue($thresholds['humidityHigh']) : 'Not available',
                'percentage' => $this->thresholdService->scaleTelemetryPercentage($telemetry->humidity !== null ? (float) $telemetry->humidity : null, $thresholds['humidityLow'], $thresholds['humidityHigh']),
                'optimalRange' => $thresholds['humidityTargetLabel'],
                'lastCalibrated' => $updatedAt,
            ],
            [
                'name' => 'Water Solution Temp',
                'value' => $telemetry->water_temperature !== null ? number_format((float) $telemetry->water_temperature, 1) : '--',
                'unit' => '°C',
                'status' => $waterTempStatus,
                'statusType' => $waterTempType,
                'min' => $thresholds['waterTemperatureLow'] !== null ? $this->thresholdService->formatThresholdValue($thresholds['waterTemperatureLow']) : 'Not available',
                'max' => $thresholds['waterTemperatureHigh'] !== null ? $this->thresholdService->formatThresholdValue($thresholds['waterTemperatureHigh']) : 'Not available',
                'percentage' => $this->thresholdService->scaleTelemetryPercentage($telemetry->water_temperature !== null ? (float) $telemetry->water_temperature : null, $thresholds['waterTemperatureLow'], $thresholds['waterTemperatureHigh']),
                'optimalRange' => $thresholds['waterTemperatureTargetLabel'],
                'lastCalibrated' => $updatedAt,
            ],
            [
                'name' => 'Hydroponic Solution pH',
                'value' => $telemetry->ph !== null ? number_format((float) $telemetry->ph, 1) : '--',
                'unit' => 'pH',
                'status' => $phStatus,
                'statusType' => $phType,
                'min' => $thresholds['phLow'] !== null ? $this->thresholdService->formatThresholdValue($thresholds['phLow']) : 'Not available',
                'max' => $thresholds['phHigh'] !== null ? $this->thresholdService->formatThresholdValue($thresholds['phHigh']) : 'Not available',
                'percentage' => $this->thresholdService->scaleTelemetryPercentage($telemetry->ph !== null ? (float) $telemetry->ph : null, $thresholds['phLow'], $thresholds['phHigh']),
                'optimalRange' => $thresholds['phTargetLabel'],
                'lastCalibrated' => $updatedAt,
            ],
            [
                'name' => 'Electrical Conductivity (EC)',
                'value' => $telemetry->ec !== null ? number_format((float) $telemetry->ec, 1) : '--',
                'unit' => 'mS/cm',
                'status' => $ecStatus,
                'statusType' => $ecType,
                'min' => $thresholds['ecLow'] !== null ? $this->thresholdService->formatThresholdValue($thresholds['ecLow']) : 'Not available',
                'max' => $thresholds['ecHigh'] !== null ? $this->thresholdService->formatThresholdValue($thresholds['ecHigh']) : 'Not available',
                'percentage' => $this->thresholdService->scaleTelemetryPercentage($telemetry->ec !== null ? (float) $telemetry->ec : null, $thresholds['ecLow'], $thresholds['ecHigh']),
                'optimalRange' => $thresholds['ecTargetLabel'],
                'lastCalibrated' => $updatedAt,
            ],
            [
                'name' => 'Reservoir Water Level',
                'value' => $waterLevel['display'] ?? '--',
                'unit' => '%',
                'status' => $waterLevelStatus,
                'statusType' => $waterLevelType,
                'min' => $thresholds['waterLevelLow'] !== null ? $this->thresholdService->formatThresholdValue($thresholds['waterLevelLow']) : 'Not available',
                'max' => $thresholds['waterLevelHigh'] !== null ? $this->thresholdService->formatThresholdValue($thresholds['waterLevelHigh']) : 'Not available',
                'percentage' => $this->thresholdService->scaleTelemetryPercentage($waterLevel['value'] ?? null, $thresholds['waterLevelLow'], $thresholds['waterLevelHigh']),
                'optimalRange' => $thresholds['waterLevelTargetLabel'],
                'lastCalibrated' => $updatedAt,
            ],
            [
                'name' => 'Water Flow Rate',
                'value' => $telemetry->water_flow !== null ? number_format((float) $telemetry->water_flow, 1) : '--',
                'unit' => 'L/min',
                'status' => $waterFlowStatus,
                'statusType' => $waterFlowType,
                'min' => $thresholds['waterFlowLow'] !== null ? $this->thresholdService->formatThresholdValue($thresholds['waterFlowLow']) : 'Not available',
                'max' => $thresholds['waterFlowHigh'] !== null ? $this->thresholdService->formatThresholdValue($thresholds['waterFlowHigh']) : 'Not available',
                'percentage' => $this->thresholdService->scaleTelemetryPercentage($telemetry->water_flow !== null ? (float) $telemetry->water_flow : null, $thresholds['waterFlowLow'], $thresholds['waterFlowHigh']),
                'optimalRange' => $thresholds['waterFlowTargetLabel'],
                'lastCalibrated' => $updatedAt,
            ],
        ];
    }
}
