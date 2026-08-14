<?php

namespace Tests\Feature;

use App\Events\TelemetryReceived;
use App\Models\Device;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Telemetry;
use App\Services\TelemetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TelemetryReceivedBroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);

        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');
    }

    public function test_broadcasting_supports_reverb_connection(): void
    {
        $this->assertArrayHasKey('reverb', config('broadcasting.connections'));
        $this->assertSame('reverb', config('broadcasting.connections.reverb.driver'));
    }

    public function test_telemetry_received_event_broadcasts_on_private_device_channel(): void
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
        $this->assertEquals('private-devices.'.$device->id.'.telemetry', $channels[0]->name);
        $this->assertEquals('TelemetryReceived', $event->broadcastAs());
    }


    public function test_private_telemetry_channel_rejects_unauthenticated_client(): void
    {
        $device = Device::create([
            'device_id' => 'LEAF-ESP32-PRIVATE-01',
            'name' => 'ESP32 Private Device',
        ]);

        $this->post('/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-devices.'.$device->id.'.telemetry',
        ])->assertForbidden();
    }

    public function test_private_telemetry_channel_requires_telemetry_view_permission(): void
    {
        $device = Device::create([
            'device_id' => 'LEAF-ESP32-PRIVATE-02',
            'name' => 'ESP32 Private Device',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->post('/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-devices.'.$device->id.'.telemetry',
        ])->assertForbidden();
    }

    public function test_private_telemetry_channel_allows_telemetry_view_user(): void
    {
        $device = Device::create([
            'device_id' => 'LEAF-ESP32-PRIVATE-03',
            'name' => 'ESP32 Private Device',
        ]);

        $permission = Permission::create(['name' => 'Telemetry: View', 'slug' => 'telemetry.view']);
        $role = Role::create(['name' => 'Telemetry Viewer', 'slug' => 'telemetry-viewer']);
        $role->permissions()->attach($permission->id);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $this->actingAs($user)->post('/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-devices.'.$device->id.'.telemetry',
        ])->assertOk();
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
            'id',
            'device_id',
            'timestamp',
            'air_temperature',
            'humidity',
            'water_temperature',
            'ph',
            'ec',
            'water_flow',
            'water_level',
            'measured_at',
            'received_at',
        ];

        ksort($payload);
        $keys = array_keys($payload);
        sort($keys);
        sort($expectedKeys);

        $this->assertEquals($expectedKeys, $keys);
        $this->assertEquals($telemetry->id, $payload['id']);
        $this->assertEquals($device->id, $payload['device_id']);
        $this->assertEquals(24.5, $payload['air_temperature']);
        $this->assertEquals(60.0, $payload['humidity']);
        $this->assertEquals(21.0, $payload['water_temperature']);
        $this->assertEquals(6.2, $payload['ph']);
        $this->assertEquals(1.5, $payload['ec']);
        $this->assertEquals(2.1, $payload['water_flow']);
        $this->assertEquals(85.0, $payload['water_level']);
        $this->assertSame($payload['measured_at'], $payload['timestamp']);
        $this->assertArrayNotHasKey('sequence_number', $payload);
        $this->assertArrayNotHasKey('signal_strength', $payload);
        $this->assertArrayNotHasKey('battery_voltage', $payload);
    }

    public function test_telemetry_service_dispatches_telemetry_received_when_realtime_broadcast_requested(): void
    {
        Event::fake([TelemetryReceived::class]);

        $device = Device::create([
            'device_id' => 'LEAF-ESP32-MQTT-01',
            'name' => 'ESP32 MQTT Device',
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
            'measured_at' => '2026-07-25 15:00:00',
        ], broadcastRealtime: true);

        $this->assertTrue($result['created']);

        Event::assertDispatched(TelemetryReceived::class, function (TelemetryReceived $event) use ($device, $result) {
            $payload = $event->broadcastWith();

            return $event->deviceId === $device->id
                && $payload['id'] === $result['telemetry']->id
                && $payload['device_id'] === $device->id
                && $payload['timestamp'] === $payload['measured_at'];
        });
    }

    public function test_telemetry_service_stores_data_without_dispatching_telemetry_received_event(): void
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

        $this->assertDatabaseHas('telemetries', [
            'device_id' => $device->id,
            'air_temperature' => 25.0,
            'ph' => 6.0,
        ]);

        Event::assertNotDispatched(TelemetryReceived::class);
    }

    public function test_telemetry_api_endpoint_stores_data_without_dispatching_event(): void
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

        Event::assertNotDispatched(TelemetryReceived::class);
    }
}
