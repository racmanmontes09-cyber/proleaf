<?php

namespace Tests\Feature;

use App\Console\Commands\MqttSubscribe;
use App\Events\TelemetryReceived;
use App\Models\Device;
use App\Services\TelemetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MqttTelemetryIngestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_mqtt_telemetry_message_is_stored_and_broadcast_once(): void
    {
        Event::fake([TelemetryReceived::class]);

        $device = Device::create([
            'device_id' => 'LEAF-MQTT-01',
            'name' => 'MQTT Device',
        ]);

        $payload = [
            'device_id' => $device->device_id,
            'air_temperature' => 24.8,
            'humidity' => 68,
            'water_temperature' => 22.4,
            'ph' => 6.3,
            'ec' => 1.9,
            'water_flow' => 2.4,
            'water_level' => 84,
            'measured_at' => '2026-07-25T12:00:00Z',
            'sequence_number' => 100,
            'firmware_version' => '1.0.0',
        ];

        $subscriber = app(MqttSubscribe::class);
        $subscriber->handleTelemetryMessage(
            'devices/'.$device->device_id.'/telemetry',
            json_encode($payload, JSON_THROW_ON_ERROR),
        );
        $subscriber->handleTelemetryMessage(
            'devices/'.$device->device_id.'/telemetry',
            json_encode($payload, JSON_THROW_ON_ERROR),
        );

        $this->assertDatabaseCount('telemetries', 1);
        $this->assertDatabaseHas('telemetries', [
            'device_id' => $device->id,
            'air_temperature' => 24.8,
            'measured_at' => '2026-07-25 12:00:00.000000',
        ]);

        Event::assertDispatchedTimes(TelemetryReceived::class, 1);
    }

    public function test_fast_telemetry_storage_treats_duplicate_as_noop(): void
    {
        Event::fake([TelemetryReceived::class]);

        $device = Device::create([
            'device_id' => 'LEAF-MQTT-DUP-01',
            'name' => 'MQTT Duplicate Device',
        ]);

        $payload = [
            'device_id' => $device->device_id,
            'air_temperature' => 24.8,
            'measured_at' => '2026-07-25T12:00:00Z',
        ];

        $service = app(TelemetryService::class);
        $first = $service->storeTelemetryFast($device, $payload);
        $duplicate = $service->storeTelemetryFast($device, $payload);

        $this->assertTrue($first['created']);
        $this->assertFalse($duplicate['created']);
        $this->assertDatabaseCount('telemetries', 1);

        Event::assertDispatchedTimes(TelemetryReceived::class, 1);
    }

    public function test_mqtt_telemetry_can_resolve_device_from_topic(): void
    {
        Event::fake([TelemetryReceived::class]);

        $device = Device::create([
            'device_id' => 'LEAF-MQTT-TOPIC-01',
            'name' => 'MQTT Topic Device',
        ]);

        app(MqttSubscribe::class)->handleTelemetryMessage(
            'devices/'.$device->device_id.'/telemetry',
            json_encode([
                'ph' => 6.4,
                'water_temperature' => 22.1,
                'measured_at' => '2026-07-25T12:00:01Z',
            ], JSON_THROW_ON_ERROR),
        );

        $this->assertDatabaseHas('telemetries', [
            'device_id' => $device->id,
            'ph' => 6.4,
            'measured_at' => '2026-07-25 12:00:01.000000',
        ]);

        Event::assertDispatchedTimes(TelemetryReceived::class, 1);
    }
}
