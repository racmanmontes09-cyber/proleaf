<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminParameterLogsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        Role::firstOrCreate(['slug' => 'viewer'], ['name' => 'Viewer']);
    }

    public function test_admin_can_view_parameter_logs_for_approved_telemetry_fields(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $device = Device::create([
            'device_id' => 'LEAF-NFT-01',
            'name' => 'NFT Lettuce ESP32',
            'last_seen_at' => now(),
        ]);

        $device->telemetries()->create([
            'air_temperature' => 24.8,
            'water_temperature' => 22.4,
            'ph' => 6.3,
            'ec' => 1.9,
            'water_flow' => 2.4,
            'water_level' => 84,
            'measured_at' => now()->setTime(10, 30),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.parameter-logs'));

        $response->assertOk()
            ->assertSee('Parameter Logs')
            ->assertSee('NFT Lettuce ESP32')
            ->assertSee('6.3')
            ->assertSee('1.9 mS/cm')
            ->assertSee('24.8 °C')
            ->assertSee('22.4 °C')
            ->assertSee('2.4 L/min')
            ->assertSee('84%');
    }

    public function test_parameter_logs_filter_by_device_and_date(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $device = Device::create(['device_id' => 'LEAF-NFT-A', 'name' => 'NFT A']);
        $otherDevice = Device::create(['device_id' => 'LEAF-NFT-B', 'name' => 'NFT B']);

        $device->telemetries()->create([
            'ph' => 6.1,
            'ec' => 1.4,
            'measured_at' => '2026-08-20 09:00:00',
        ]);
        $device->telemetries()->create([
            'ph' => 6.6,
            'ec' => 1.8,
            'measured_at' => '2026-08-25 09:00:00',
        ]);
        $otherDevice->telemetries()->create([
            'ph' => 7.4,
            'ec' => 2.5,
            'measured_at' => '2026-08-25 09:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.parameter-logs', [
            'deviceId' => $device->id,
            'fromDate' => '2026-08-24',
            'toDate' => '2026-08-26',
        ]));

        $response->assertOk()
            ->assertSee('>6.6<', false)
            ->assertSee('>1.8 mS/cm<', false)
            ->assertDontSee('>6.1<', false)
            ->assertDontSee('>7.4<', false);
    }

    public function test_parameter_logs_are_not_accessible_to_viewer_role(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');

        $this->actingAs($viewer)->get(route('admin.parameter-logs'))->assertForbidden();
    }
}