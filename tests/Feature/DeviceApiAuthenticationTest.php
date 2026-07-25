<?php

namespace Tests\Feature;

use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_device_authentication_allows_heartbeat_request(): void
    {
        [$device, $token] = $this->createAuthenticatedDevice();

        $this->withToken($token)->postJson('/api/devices/heartbeat', [
            'firmware_version' => '2.0.1',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.uuid', $device->uuid)
            ->assertJsonPath('data.device_id', $device->device_id);
    }

    public function test_missing_device_token_is_rejected(): void
    {
        $this->postJson('/api/devices/heartbeat', [
            'firmware_version' => '2.0.1',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Missing device token.',
            ]);
    }

    public function test_invalid_device_token_is_rejected(): void
    {
        $this->withToken('invalid-token')->postJson('/api/devices/heartbeat', [
            'firmware_version' => '2.0.1',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Invalid device token.',
            ]);
    }

    public function test_disabled_device_is_rejected(): void
    {
        [, $token] = $this->createAuthenticatedDevice([
            'status' => Device::STATUS_DISABLED,
        ]);

        $this->withToken($token)->postJson('/api/devices/heartbeat', [
            'firmware_version' => '2.0.1',
        ])
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Device disabled.',
            ]);
    }

    public function test_revoked_device_token_is_rejected(): void
    {
        [$device, $token] = $this->createAuthenticatedDevice();

        $device->forceFill([
            'device_token_revoked_at' => now(),
        ])->save();

        $this->withToken($token)->postJson('/api/devices/heartbeat', [
            'firmware_version' => '2.0.1',
        ])
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Device token revoked.',
            ]);
    }

    public function test_expired_device_token_is_rejected(): void
    {
        [, $token] = $this->createAuthenticatedDevice([], now()->subMinute());

        $this->withToken($token)->postJson('/api/devices/heartbeat', [
            'firmware_version' => '2.0.1',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Device token expired.',
            ]);
    }

    public function test_heartbeat_success_updates_authenticated_device_metadata(): void
    {
        [$device, $token] = $this->createAuthenticatedDevice([
            'device_id' => 'LEAF-ESP32-HEARTBEAT',
            'name' => 'Old Device Name',
        ]);

        $this->withToken($token)->postJson('/api/devices/heartbeat', [
            'device_id' => 'SPOOFED-DEVICE-ID',
            'name' => 'Greenhouse Controller',
            'firmware_version' => '2.1.0',
            'local_ip_address' => '192.168.1.42',
            'wifi_rssi' => -58,
            'uptime_seconds' => 3600,
            'free_heap' => 182000,
            'last_boot_at' => '2026-07-25 08:00:00',
        ])->assertOk();

        $device->refresh();

        $this->assertSame('LEAF-ESP32-HEARTBEAT', $device->device_id);
        $this->assertSame('Greenhouse Controller', $device->name);
        $this->assertSame('2.1.0', $device->firmware_version);
        $this->assertSame('192.168.1.42', $device->local_ip_address);
        $this->assertSame(-58, $device->wifi_rssi);
        $this->assertSame(3600, $device->uptime_seconds);
        $this->assertSame(182000, $device->free_heap);
        $this->assertNotNull($device->last_seen_at);
        $this->assertNotNull($device->device_token_last_used_at);
    }

    public function test_heartbeat_endpoint_is_rate_limited(): void
    {
        config(['leaf.device_api.rate_limits.heartbeat_per_minute' => 2]);

        [, $token] = $this->createAuthenticatedDevice();

        $this->withToken($token)->postJson('/api/devices/heartbeat', [
            'firmware_version' => '2.0.1',
        ])->assertOk();

        $this->withToken($token)->postJson('/api/devices/heartbeat', [
            'firmware_version' => '2.0.1',
        ])->assertOk();

        $this->withToken($token)->postJson('/api/devices/heartbeat', [
            'firmware_version' => '2.0.1',
        ])->assertTooManyRequests();
    }

    public function test_telemetry_endpoint_is_rate_limited(): void
    {
        config(['leaf.device_api.rate_limits.telemetry_per_minute' => 2]);

        [, $token] = $this->createAuthenticatedDevice();

        $payload = ['air_temperature' => 24.8];

        $this->withToken($token)->postJson('/api/devices/telemetry', $payload)->assertCreated();
        $this->withToken($token)->postJson('/api/devices/telemetry', $payload)->assertCreated();
        $this->withToken($token)->postJson('/api/devices/telemetry', $payload)->assertTooManyRequests();
    }

    private function createAuthenticatedDevice(array $attributes = [], $expiresAt = null): array
    {
        $device = Device::create(array_merge([
            'device_id' => 'LEAF-ESP32-01',
            'name' => 'ESP32 Device',
        ], $attributes));

        $token = $device->issueDeviceToken($expiresAt);

        return [$device->refresh(), $token];
    }
}
