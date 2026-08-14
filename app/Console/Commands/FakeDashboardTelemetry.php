<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\Telemetry;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class FakeDashboardTelemetry extends Command
{
    protected $signature = 'leaf:fake-dashboard-telemetry
        {device=358 : Device database primary key stored in telemetries.device_id}
        {--count=50 : Number of telemetry records to insert}
        {--replace : Delete previous fake dashboard telemetry rows for this device first}
        {--clear : Delete previous fake dashboard telemetry rows for this device and exit}';

    protected $description = 'Insert fake dashboard telemetry records for local visualization testing.';

    private const FIRMWARE_VERSION = 'leaf-fake-dashboard-telemetry';

    private const SENSOR_PROFILE = [
        'air_temperature' => ['min' => 29.0, 'max' => 34.0, 'decimals' => 1, 'period' => 9.0, 'phase' => 0.1],
        'humidity' => ['min' => 60.0, 'max' => 80.0, 'decimals' => 0, 'period' => 11.0, 'phase' => 1.3],
        'water_temperature' => ['min' => 25.0, 'max' => 29.0, 'decimals' => 1, 'period' => 13.0, 'phase' => 2.1],
        'ph' => ['min' => 5.8, 'max' => 6.8, 'decimals' => 2, 'period' => 15.0, 'phase' => 0.8],
        'ec' => ['min' => 1.0, 'max' => 1.8, 'decimals' => 2, 'period' => 17.0, 'phase' => 2.7],
        'water_flow' => ['min' => 0.8, 'max' => 1.8, 'decimals' => 2, 'period' => 8.0, 'phase' => 2.9],
        'water_level' => ['min' => 65.0, 'max' => 90.0, 'decimals' => 0, 'period' => 24.0, 'phase' => 1.9],
    ];

    public function handle(): int
    {
        $deviceId = (int) $this->argument('device');
        $count = (int) $this->option('count');

        if ($deviceId < 1) {
            $this->error('Device database id must be greater than zero.');

            return self::FAILURE;
        }

        $device = Device::query()->find($deviceId);
        if (! $device instanceof Device) {
            $this->error("Device database id {$deviceId} was not found.");

            return self::FAILURE;
        }

        if ((bool) $this->option('clear')) {
            $deleted = $this->deleteFakeTelemetry($device);
            $this->forgetDashboardTelemetryCache($device);

            $this->info("Deleted {$deleted} fake dashboard telemetry rows for device {$deviceId}.");

            return self::SUCCESS;
        }

        if ($count < 1) {
            $this->error('--count must be at least 1.');

            return self::FAILURE;
        }

        if ((bool) $this->option('replace')) {
            $deleted = $this->deleteFakeTelemetry($device);

            $this->line("Deleted {$deleted} previous fake dashboard telemetry rows for device {$deviceId}.");
        }

        $sequence = (int) ($device->telemetries()->max('sequence_number') ?? 0);
        $endAt = now()->subSecond();
        $records = [];

        foreach (range(1, $count) as $index) {
            $sequence++;
            $measuredAt = $endAt->copy()
                ->subSeconds(($count - $index) * 60)
                ->setMicrosecond($index);
            $receivedAt = $measuredAt->copy()->addMilliseconds(350);

            $records[] = [
                'device_id' => $device->id,
                'air_temperature' => $this->sensorValue('air_temperature', $sequence),
                'humidity' => $this->sensorValue('humidity', $sequence),
                'water_temperature' => $this->sensorValue('water_temperature', $sequence),
                'ph' => $this->sensorValue('ph', $sequence),
                'ec' => $this->sensorValue('ec', $sequence),
                'water_flow' => $this->sensorValue('water_flow', $sequence),
                'water_level' => $this->sensorValue('water_level', $sequence),
                'measured_at' => $this->formatTimestamp($measuredAt),
                'received_at' => $this->formatTimestamp($receivedAt),
                'sequence_number' => $sequence,
                'firmware_version' => self::FIRMWARE_VERSION,
                'signal_strength' => (int) round($this->boundedWave($sequence, -72, -48, 10.0, 0.4)),
                'battery_voltage' => round($this->boundedWave($sequence, 4.7, 5.1, 18.0, 1.1), 2),
                'payload_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Telemetry::query()->insert($records);

        $this->forgetDashboardTelemetryCache($device);

        $latest = $device->telemetries()
            ->where('firmware_version', self::FIRMWARE_VERSION)
            ->latestReading()
            ->first();

        $this->info("Inserted {$count} fake telemetry records for device database id {$device->id}.");
        $this->line('Dashboard device target: LEAF_DASHBOARD_DEVICE_DB_ID='.$device->id);
        $this->line(sprintf(
            'Latest fake reading: sequence %d, air %.1f C, humidity %.0f%%, water %.1f C, pH %.2f, EC %.2f, flow %.2f L/min, level %.0f%%',
            $latest?->sequence_number ?? $sequence,
            $latest?->air_temperature ?? 0,
            $latest?->humidity ?? 0,
            $latest?->water_temperature ?? 0,
            $latest?->ph ?? 0,
            $latest?->ec ?? 0,
            $latest?->water_flow ?? 0,
            $latest?->water_level ?? 0,
        ));

        return self::SUCCESS;
    }

    private function deleteFakeTelemetry(Device $device): int
    {
        return $device->telemetries()
            ->where('firmware_version', self::FIRMWARE_VERSION)
            ->delete();
    }

    private function forgetDashboardTelemetryCache(Device $device): void
    {
        Cache::forget('dashboard.telemetry-history.'.$device->id);
        Cache::forget('dashboard.telemetry-history.'.$device->id.'.live');
        Cache::forget('dashboard.telemetry-history.'.$device->id.'.'.self::FIRMWARE_VERSION);
    }

    private function sensorValue(string $field, int $sequenceNumber): float|int
    {
        $profile = self::SENSOR_PROFILE[$field];
        $value = $this->boundedWave(
            $sequenceNumber,
            $profile['min'],
            $profile['max'],
            $profile['period'],
            $profile['phase'],
        );

        $rounded = round($value, $profile['decimals']);

        return $profile['decimals'] === 0 ? (int) $rounded : $rounded;
    }

    private function boundedWave(int $sequenceNumber, float $min, float $max, float $period, float $phase): float
    {
        $center = ($min + $max) / 2;
        $amplitude = ($max - $min) * 0.42;
        $microDrift = ($max - $min) * 0.05;
        $value = $center
            + sin(($sequenceNumber / $period) + $phase) * $amplitude
            + sin(($sequenceNumber / ($period / 2)) + $phase) * $microDrift;

        return max($min, min($max, $value));
    }

    private function formatTimestamp(Carbon $timestamp): string
    {
        return $timestamp->utc()->format('Y-m-d H:i:s.u');
    }
}
