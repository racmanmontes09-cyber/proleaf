<?php

namespace Tests\Feature;

use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelemetryEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_endpoint_requires_device_authentication(): void
    {
        $this->postJson('/api/devices/telemetry', [
            'air_temperature' => 24.8,
        ])
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Missing device token.',
            ]);
    }

    public function test_telemetry_endpoint_stores_validated_sensor_data_for_authenticated_device(): void
    {
        [$device, $token] = $this->createAuthenticatedDevice([
            'device_id' => 'LEAF-ESP32-01',
            'name' => 'ESP32-01',
        ]);

        $spoofedDevice = Device::create([
            'device_id' => 'LEAF-ESP32-02',
            'name' => 'ESP32-02',
        ]);

        $response = $this->withToken($token)->postJson('/api/devices/telemetry', [
            'device_id' => $spoofedDevice->device_id,
            'air_temperature' => 24.8,
            'humidity' => 68,
            'water_temperature' => 22.4,
            'ph' => 6.3,
            'ec' => 1.9,
            'water_flow' => 2.4,
            'water_level' => 84,
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Telemetry stored successfully.',
            ]);

        $this->assertDatabaseHas('telemetries', [
            'device_id' => $device->id,
            'air_temperature' => 24.8,
            'humidity' => 68,
            'water_temperature' => 22.4,
            'ph' => 6.3,
            'ec' => 1.9,
            'water_flow' => 2.4,
            'water_level' => 84,
        ]);

        $this->assertDatabaseMissing('telemetries', [
            'device_id' => $spoofedDevice->id,
            'air_temperature' => 24.8,
        ]);
    }

    public function test_telemetry_endpoint_accepts_and_stores_new_metadata_fields(): void
    {
        [$device, $token] = $this->createAuthenticatedDevice([
            'device_id' => 'LEAF-ESP32-01',
            'name' => 'ESP32-01',
        ]);

        $measuredAt = '2026-07-25 12:00:00';

        $response = $this->withToken($token)->postJson('/api/devices/telemetry', [
            'air_temperature' => 25.5,
            'measured_at' => $measuredAt,
            'sequence_number' => 1042,
            'firmware_version' => 'v1.2.0-leaf',
            'signal_strength' => -67,
            'battery_voltage' => 4.12,
            'payload_version' => 1,
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Telemetry stored successfully.',
            ]);

        $this->assertDatabaseHas('telemetries', [
            'device_id' => $device->id,
            'air_temperature' => 25.5,
            'measured_at' => '2026-07-25 12:00:00.000000',
            'sequence_number' => 1042,
            'firmware_version' => 'v1.2.0-leaf',
            'signal_strength' => -67,
            'battery_voltage' => 4.12,
            'payload_version' => 1,
        ]);
    }

    public function test_telemetry_endpoint_normalizes_offset_measured_at_to_utc(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-08-11 14:00:00 UTC'));

        [$device, $token] = $this->createAuthenticatedDevice();

        $this->withToken($token)->postJson('/api/devices/telemetry', [
            'air_temperature' => 24.8,
            'measured_at' => '2026-08-11T21:55:31.123456+08:00',
        ])->assertCreated();

        $telemetry = $device->telemetries()->latest('id')->firstOrFail();

        $this->assertSame('2026-08-11 13:55:31.123456', $telemetry->getRawOriginal('measured_at'));
        $this->assertTrue($telemetry->measured_at->lessThanOrEqualTo(now()));
    }

    public function test_telemetry_endpoint_handles_duplicate_measured_at_telemetry_gracefully(): void
    {
        [$device, $token] = $this->createAuthenticatedDevice([
            'device_id' => 'LEAF-ESP32-01',
            'name' => 'ESP32-01',
        ]);

        $payload = [
            'air_temperature' => 26.0,
            'measured_at' => '2026-07-25 12:30:00',
            'sequence_number' => 500,
        ];

        // First creation succeeds
        $this->withToken($token)->postJson('/api/devices/telemetry', $payload)
            ->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Telemetry stored successfully.',
            ]);

        // Duplicate submission ignored gracefully
        $this->withToken($token)->postJson('/api/devices/telemetry', $payload)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Telemetry already stored.',
            ]);

        $this->assertDatabaseCount('telemetries', 1);
    }


    public function test_telemetry_endpoint_rejects_future_measured_at(): void
    {
        [, $token] = $this->createAuthenticatedDevice();

        $this->withToken($token)->postJson('/api/devices/telemetry', [
            'air_temperature' => 24.8,
            'measured_at' => now()->addMinute()->toIso8601String(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('measured_at');
    }

    public function test_telemetry_endpoint_missing_measured_at_is_not_future_skewed(): void
    {
        [$device, $token] = $this->createAuthenticatedDevice();

        $this->withToken($token)->postJson('/api/devices/telemetry', [
            'air_temperature' => 24.8,
        ])->assertCreated();

        $telemetry = $device->telemetries()->latest('id')->firstOrFail();

        $this->assertTrue(
            $telemetry->measured_at->lessThanOrEqualTo(now()),
            'Telemetry measured_at should not be shifted into the future when omitted.'
        );
    }

    public function test_telemetry_endpoint_rejects_malformed_payloads(): void
    {
        [, $token] = $this->createAuthenticatedDevice();

        $this->withToken($token)->postJson('/api/devices/telemetry', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('telemetry');
    }

    private function createAuthenticatedDevice(array $attributes = []): array
    {
        $device = Device::create(array_merge([
            'device_id' => 'LEAF-ESP32-01',
            'name' => 'ESP32 Device',
        ], $attributes));

        $token = $device->issueDeviceToken();

        return [$device->refresh(), $token];
    }
}
