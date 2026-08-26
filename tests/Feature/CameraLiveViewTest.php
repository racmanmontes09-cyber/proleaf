<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CameraLiveViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_stream_url_requires_authentication(): void
    {
        $this->getJson('/dashboard/camera/stream-url')
            ->assertRedirect('/login');
    }

    public function test_stream_url_reports_unavailable_when_no_camera_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/dashboard/camera/stream-url');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('available', false)
            ->assertJsonPath('url', null);
    }

    public function test_camera_heartbeat_registers_lan_address_for_live_view(): void
    {
        $device = Device::create([
            'device_id' => 'esp32-cam-001',
            'name' => 'Greenhouse 1 Camera',
            'type' => Device::TYPE_CAMERA,
        ]);
        $token = $device->issueDeviceToken();

        $this->withToken($token)->postJson('/api/devices/heartbeat', [
            'type' => 'camera',
            'firmware_version' => '0.1.0',
            'local_ip_address' => '192.168.1.42',
            'wifi_rssi' => -58,
            'uptime_seconds' => 120,
            'free_heap' => 123456,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame(Device::TYPE_CAMERA, $device->refresh()->type);
        $this->assertSame('192.168.1.42', $device->local_ip_address);

        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/dashboard/camera/stream-url')
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('online', true)
            ->assertJsonPath('device_id', 'esp32-cam-001')
            ->assertJsonPath('url', 'http://192.168.1.42:81/stream')
            ->assertJsonPath('audio_url', 'http://192.168.1.42:82/audio');
    }

    public function test_sensor_devices_are_never_served_as_camera_streams(): void
    {
        Device::create([
            'device_id' => 'LEAF-ESP32-01',
            'name' => 'ESP32 Sensor',
            'type' => Device::TYPE_SENSOR,
            'local_ip_address' => '192.168.1.50',
            'last_seen_at' => now(),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/dashboard/camera/stream-url')
            ->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonPath('url', null);
    }

    public function test_disabled_cameras_are_not_served_as_streams(): void
    {
        Device::create([
            'device_id' => 'esp32-cam-001',
            'type' => Device::TYPE_CAMERA,
            'status' => Device::STATUS_DISABLED,
            'local_ip_address' => '192.168.1.42',
            'last_seen_at' => now(),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/dashboard/camera/stream-url')
            ->assertOk()
            ->assertJsonPath('available', false);
    }

    public function test_provision_camera_command_prints_a_working_device_token(): void
    {
        Artisan::call('leaf:provision-camera', [
            'device_id' => 'esp32-cam-002',
            '--name' => 'Greenhouse 2 Camera',
        ]);
        $output = Artisan::output();

        $this->assertStringContainsString('esp32-cam-002', $output);
        $this->assertStringContainsString('Greenhouse 2 Camera', $output);

        $device = Device::query()->where('device_id', 'esp32-cam-002')->firstOrFail();
        $this->assertSame(Device::TYPE_CAMERA, $device->type);

        $this->assertSame(1, preg_match('/leaf_[A-Za-z0-9]+/', $output, $matches));
        $plainToken = $matches[0];

        $this->withToken($plainToken)->postJson('/api/devices/heartbeat', [
            'type' => 'camera',
            'local_ip_address' => '192.168.1.77',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame('192.168.1.77', $device->refresh()->local_ip_address);
    }

    public function test_provision_camera_refuses_to_reuse_a_sensor_device_id(): void
    {
        Device::create([
            'device_id' => 'LEAF-ESP32-01',
            'type' => Device::TYPE_SENSOR,
        ]);

        Artisan::call('leaf:provision-camera', ['device_id' => 'LEAF-ESP32-01']);

        $this->assertStringContainsString('already exists', Artisan::output());
    }
}
