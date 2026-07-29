<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Telemetry;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TelemetryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedThresholdSettings();

        $device = Device::factory()->create([
            'device_id' => 'LEAF-ESP32-01',
            'name' => 'LEAF Sensor Node 01',
            'firmware_version' => 'v1.2.3',
            'local_ip_address' => '192.168.1.42',
            'wifi_rssi' => -55,
            'uptime_seconds' => 3600 * 5,
            'free_heap' => 120000,
            'last_boot_at' => now()->subHours(5),
            'last_seen_at' => now(),
        ]);

        $this->seedHistoricalTelemetry($device);
        $this->seedWarningTelemetry($device);
        $this->seedCriticalTelemetry($device);
        $this->seedOfflineDevice();
    }

    protected function seedThresholdSettings(): void
    {
        $defaults = [
            ['key' => 'temperature_min', 'value' => 18.0],
            ['key' => 'temperature_max', 'value' => 25.0],
            ['key' => 'humidity_min', 'value' => 60.0],
            ['key' => 'humidity_max', 'value' => 80.0],
            ['key' => 'water_temperature_min', 'value' => 18.0],
            ['key' => 'water_temperature_max', 'value' => 24.0],
            ['key' => 'ph_min', 'value' => 5.5],
            ['key' => 'ph_max', 'value' => 6.5],
            ['key' => 'ec_min', 'value' => 1.2],
            ['key' => 'ec_max', 'value' => 2.0],
            ['key' => 'water_flow_min', 'value' => 0.5],
            ['key' => 'water_flow_max', 'value' => 2.0],
            ['key' => 'water_level_min', 'value' => 20.0],
            ['key' => 'water_level_max', 'value' => 80.0],
            ['key' => 'heartbeat_interval', 'value' => 30],
        ];

        foreach ($defaults as $setting) {
            SystemSetting::putValue($setting['key'], $setting['value'], [
                'label' => ucfirst(str_replace('_', ' ', $setting['key'])),
                'type' => is_int($setting['value']) ? 'integer' : 'string',
                'group' => 'thresholds',
            ]);
        }
    }

    protected function seedHistoricalTelemetry(Device $device): void
    {
        $start = now()->subHours(24);
        $records = [];

        for ($i = 0; $i < 96; $i++) {
            $measuredAt = $start->copy()->addMinutes($i * 15);
            $records[] = $this->buildTelemetryPayload($measuredAt, 'healthy');
        }

        foreach (array_chunk($records, 1000) as $chunk) {
            foreach ($chunk as $payload) {
                $device->telemetries()->create($payload);
            }
        }
    }

    protected function seedWarningTelemetry(Device $device): void
    {
        $times = [now()->subMinutes(45), now()->subMinutes(30), now()->subMinutes(15)];

        foreach ($times as $measuredAt) {
            $device->telemetries()->create($this->buildTelemetryPayload($measuredAt, 'warning'));
        }
    }

    protected function seedCriticalTelemetry(Device $device): void
    {
        $times = [now()->subMinutes(10), now()->subMinutes(5)];

        foreach ($times as $measuredAt) {
            $device->telemetries()->create($this->buildTelemetryPayload($measuredAt, 'critical'));
        }
    }

    protected function seedOfflineDevice(): void
    {
        Device::factory()->create([
            'device_id' => 'LEAF-ESP32-02',
            'name' => 'LEAF Sensor Node 02',
            'firmware_version' => 'v1.2.3',
            'local_ip_address' => '192.168.1.43',
            'wifi_rssi' => -72,
            'uptime_seconds' => 3600 * 12,
            'free_heap' => 98000,
            'last_boot_at' => now()->subHours(12),
            'last_seen_at' => now()->subMinutes(120),
        ]);
    }

    protected function buildTelemetryPayload(Carbon $measuredAt, string $condition): array
    {
        switch ($condition) {
            case 'warning':
                return [
                    'air_temperature' => 26.5,
                    'humidity' => 58.0,
                    'water_temperature' => 24.5,
                    'ph' => 6.4,
                    'ec' => 2.1,
                    'water_flow' => 2.1,
                    'water_level' => 83.0,
                    'measured_at' => $measuredAt,
                    'received_at' => $measuredAt->copy()->addSeconds(30),
                    'sequence_number' => rand(10000, 20000),
                    'firmware_version' => 'v1.2.3',
                    'signal_strength' => -60,
                    'battery_voltage' => 4.05,
                    'payload_version' => 1,
                ];
            case 'critical':
                return [
                    'air_temperature' => 31.0,
                    'humidity' => 52.0,
                    'water_temperature' => 26.5,
                    'ph' => 7.4,
                    'ec' => 2.5,
                    'water_flow' => 0.3,
                    'water_level' => 55.0,
                    'measured_at' => $measuredAt,
                    'received_at' => $measuredAt->copy()->addSeconds(30),
                    'sequence_number' => rand(20001, 21000),
                    'firmware_version' => 'v1.2.3',
                    'signal_strength' => -65,
                    'battery_voltage' => 3.85,
                    'payload_version' => 1,
                ];
            case 'healthy':
            default:
                return [
                    'air_temperature' => $this->randomFloat(24.0, 28.0),
                    'humidity' => $this->randomFloat(60.0, 75.0),
                    'water_temperature' => $this->randomFloat(20.0, 24.0),
                    'ph' => $this->randomFloat(5.8, 6.3),
                    'ec' => $this->randomFloat(1.2, 1.8),
                    'water_flow' => $this->randomFloat(1.0, 2.0),
                    'water_level' => $this->randomFloat(85.0, 100.0),
                    'measured_at' => $measuredAt,
                    'received_at' => $measuredAt->copy()->addSeconds(30),
                    'sequence_number' => rand(1, 9999),
                    'firmware_version' => 'v1.2.3',
                    'signal_strength' => rand(-70, -50),
                    'battery_voltage' => $this->randomFloat(3.9, 4.2),
                    'payload_version' => 1,
                ];
        }
    }

    protected function randomFloat(float $min, float $max, int $decimals = 1): float
    {
        $factor = 10 ** $decimals;

        return mt_rand((int) ($min * $factor), (int) ($max * $factor)) / $factor;
    }
}
