<?php

namespace Tests\Feature;

use App\Events\TelemetryReceived;
use App\Models\Device;
use App\Models\Telemetry;
use App\Services\TelemetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TelemetryReceivedBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_received_event_broadcasts_on_telemetry_channel(): void
    {
        $device = Device::create([
            'device_id' => 'LEAF-ESP32-01',
            'name' => 'ESP32 Device',
        ]);

        $telemetry = $device->telemetries()->create([
            'air_temperature' => 24.5,
            'humidity' => 60.0,
            'water_temperature' => 21.0,
            'ph' => 6.2,
            'ec' => 1.5,
            'water_flow' => 2.1,
            'water_level' => 85.0,
            'measured_at' => '2026-07-25 14:00:00',
        ]);

        $event = new TelemetryReceived($telemetry);

        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertEquals('telemetry', $channels[0]->name);
        $this->assertEquals('TelemetryReceived', $event->broadcastAs());
    }

    public function test_telemetry_received_event_payload_contains_only_required_fields(): void
    {
        $device = Device::create([
            'device_id' => 'LEAF-ESP32-01',
            'name' => 'ESP32 Device',
        ]);

        $telemetry = $device->telemetries()->create([
            'air_temperature' => 24.5,
            'humidity' => 60.0,
            'water_temperature' => 21.0,
            'ph' => 6.2,
            'ec' => 1.5,
            'water_flow' => 2.1,
            'water_level' => 85.0,
            'measured_at' => '2026-07-25 14:00:00',
            'sequence_number' => 100,
            'signal_strength' => -70,
            'battery_voltage' => 4.1,
        ]);

        $event = new TelemetryReceived($telemetry);
        $payload = $event->broadcastWith();

        $expectedKeys = [
            'device_id',
            'air_temperature',
            'humidity',
            'water_temperature',
            'ph',
            'ec',
            'water_flow',
            'water_level',
            'measured_at',
        ];

        ksort($payload);
        $keys = array_keys($payload);
        sort($keys);
        sort($expectedKeys);

        $this->assertEquals($expectedKeys, $keys);
        $this->assertEquals($device->id, $payload['device_id']);
        $this->assertEquals(24.5, $payload['air_temperature']);
        $this->assertEquals(60.0, $payload['humidity']);
        $this->assertEquals(21.0, $payload['water_temperature']);
        $this->assertEquals(6.2, $payload['ph']);
        $this->assertEquals(1.5, $payload['ec']);
        $this->assertEquals(2.1, $payload['water_flow']);
        $this->assertEquals(85.0, $payload['water_level']);
        $this->assertArrayNotHasKey('sequence_number', $payload);
        $this->assertArrayNotHasKey('signal_strength', $payload);
        $this->assertArrayNotHasKey('battery_voltage', $payload);
    }

    public function test_telemetry_service_dispatches_telemetry_received_event(): void
    {
        Event::fake([TelemetryReceived::class]);

        $device = Device::create([
            'device_id' => 'LEAF-ESP32-01',
            'name' => 'ESP32 Device',
        ]);

        $service = app(TelemetryService::class);
        $result = $service->storeTelemetry($device, [
            'air_temperature' => 25.0,
            'humidity' => 65.0,
            'water_temperature' => 22.0,
            'ph' => 6.0,
            'ec' => 1.6,
            'water_flow' => 2.0,
            'water_level' => 90.0,
        ]);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['created']);

        Event::assertDispatched(TelemetryReceived::class, function ($event) use ($device) {
            return $event->telemetry->device_id === $device->id
                && $event->telemetry->air_temperature == 25.0;
        });
    }

    public function test_telemetry_api_endpoint_stores_data_and_dispatches_event(): void
    {
        Event::fake([TelemetryReceived::class]);

        $device = Device::create([
            'device_id' => 'LEAF-ESP32-01',
            'name' => 'ESP32 Device',
        ]);
        $token = $device->issueDeviceToken();

        $response = $this->withToken($token)->postJson('/api/devices/telemetry', [
            'air_temperature' => 26.2,
            'humidity' => 64.0,
            'water_temperature' => 21.8,
            'ph' => 6.1,
            'ec' => 1.7,
            'water_flow' => 2.3,
            'water_level' => 88.0,
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Telemetry stored successfully.',
            ]);

        $this->assertDatabaseHas('telemetries', [
            'device_id' => $device->id,
            'air_temperature' => 26.2,
            'ph' => 6.1,
        ]);

        Event::assertDispatched(TelemetryReceived::class);
    }
}
