<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTelemetryReadingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_telemetry_readings_require_authentication(): void
    {
        $this->getJson('/dashboard/telemetry/readings')
            ->assertStatus(401);
    }

    public function test_dashboard_telemetry_readings_return_initial_rolling_history(): void
    {
        $user = User::factory()->create();
        $device = Device::create([
            'device_id' => 'LEAF-ESP32-01',
            'name' => 'ESP32-01',
            'last_seen_at' => now(),
        ]);

        foreach (range(1, 5) as $index) {
            $device->telemetries()->create([
                'ph' => 6 + ($index / 10),
                'water_temperature' => 22 + $index,
                'measured_at' => now()->subSeconds(6 - $index),
            ]);
        }

        $response = $this->actingAs($user)->getJson('/dashboard/telemetry/readings?device_id='.$device->id.'&limit=3');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('device_id', $device->id)
            ->assertJsonCount(3, 'readings')
            ->assertJsonPath('readings.0.ph', 6.3)
            ->assertJsonPath('readings.2.ph', 6.5)
            ->assertJsonPath('latest_kpis.ph.value', '6.5')
            ->assertJsonPath('latest_kpis.ph.status', 'NORMAL')
            ->assertJsonPath('latest_kpis.water_temperature.value', '27.0');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame(
            $device->telemetries()->latest('id')->value('id'),
            $response->json('latest_id')
        );
    }

    public function test_dashboard_telemetry_readings_default_to_configured_local_device_when_present(): void
    {
        $user = User::factory()->create();

        $otherDevice = Device::create([
            'device_id' => 'LEAF-ESP32-OTHER',
            'name' => 'Other ESP32',
            'last_seen_at' => now(),
        ]);
        $otherDevice->telemetries()->create([
            'ph' => 9.9,
            'measured_at' => now(),
        ]);

        $targetDevice = new Device([
            'device_id' => 'esp32-001',
            'name' => 'Greenhouse ESP32',
            'last_seen_at' => now()->subHour(),
        ]);
        $targetDevice->id = 358;
        $targetDevice->save();

        $targetDevice->telemetries()->create([
            'air_temperature' => 30.5,
            'humidity' => 72,
            'water_temperature' => 26.4,
            'ph' => 6.4,
            'ec' => 1.6,
            'water_flow' => 1.2,
            'water_level' => 74,
            'measured_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)->getJson('/dashboard/telemetry/readings');

        $response->assertOk()
            ->assertJsonPath('device_id', 358)
            ->assertJsonPath('readings.0.ph', 6.4)
            ->assertJsonPath('latest_kpis.air_temperature.value', '30.5')
            ->assertJsonPath('latest_kpis.water_level.value', '74');
    }

    public function test_dashboard_telemetry_readings_return_real_telemetry_even_when_fake_rows_exist(): void
    {
        $user = User::factory()->create();

        $targetDevice = new Device([
            'device_id' => 'esp32-001',
            'name' => 'Greenhouse ESP32',
            'last_seen_at' => now(),
        ]);
        $targetDevice->id = 358;
        $targetDevice->save();

        $targetDevice->telemetries()->create([
            'air_temperature' => 29.5,
            'humidity' => 64,
            'water_temperature' => 25.1,
            'ph' => 6.2,
            'ec' => 1.4,
            'water_flow' => 0.9,
            'water_level' => 68,
            'measured_at' => now()->subMinutes(6),
            'firmware_version' => 'leaf-fake-dashboard-telemetry',
        ]);

        $oldReal = $targetDevice->telemetries()->create([
            'air_temperature' => 30.5,
            'humidity' => 72,
            'water_temperature' => 26.4,
            'ph' => 6.4,
            'ec' => 1.6,
            'water_flow' => 1.2,
            'water_level' => 74,
            'measured_at' => now()->subMinutes(5),
            'firmware_version' => '1.0.0',
        ]);

        $newOne = $targetDevice->telemetries()->create([
            'air_temperature' => 31.1,
            'humidity' => 73,
            'water_temperature' => 26.8,
            'ph' => 6.5,
            'ec' => 1.7,
            'water_flow' => 1.3,
            'water_level' => 75,
            'measured_at' => now()->subMinutes(3),
            'firmware_version' => '1.0.0',
        ]);

        $newTwo = $targetDevice->telemetries()->create([
            'air_temperature' => 31.4,
            'humidity' => 74,
            'water_temperature' => 27.0,
            'ph' => 6.6,
            'ec' => 1.8,
            'water_flow' => 1.4,
            'water_level' => 76,
            'measured_at' => now()->subMinute(),
            'firmware_version' => '1.0.0',
        ]);

        $response = $this->actingAs($user)->getJson('/dashboard/telemetry/readings?device_id='.$targetDevice->id.'&after_id='.$oldReal->id.'&limit=120');

        $response->assertOk()
            ->assertJsonPath('device_id', 358)
            ->assertJsonPath('after_id', $oldReal->id)
            ->assertJsonPath('latest_id', $newTwo->id)
            ->assertJsonCount(2, 'readings')
            ->assertJsonPath('readings.0.id', $newOne->id)
            ->assertJsonPath('readings.1.id', $newTwo->id)
            ->assertJsonPath('readings.1.air_temperature', 31.4)
            ->assertJsonPath('latest_kpis.air_temperature.value', '31.4')
            ->assertJsonPath('latest_kpis.water_flow.value', '1.4');
    }

    public function test_dashboard_telemetry_readings_filter_after_id_for_selected_device(): void
    {
        $user = User::factory()->create();
        $device = Device::create(['device_id' => 'LEAF-ESP32-01', 'name' => 'ESP32-01', 'last_seen_at' => now()]);
        $otherDevice = Device::create(['device_id' => 'LEAF-ESP32-02', 'name' => 'ESP32-02', 'last_seen_at' => now()]);

        $old = $device->telemetries()->create(['ph' => 6.1, 'measured_at' => now()->subSeconds(4)]);
        $newOne = $device->telemetries()->create(['ph' => 6.2, 'measured_at' => now()->subSeconds(3)]);
        $otherDevice->telemetries()->create(['ph' => 9.9, 'measured_at' => now()->subSeconds(2)]);
        $newTwo = $device->telemetries()->create(['ph' => 6.3, 'measured_at' => now()->subSecond()]);

        $response = $this->actingAs($user)->getJson('/dashboard/telemetry/readings?device_id='.$device->id.'&after_id='.$old->id);

        $response->assertOk()
            ->assertJsonCount(2, 'readings')
            ->assertJsonPath('readings.0.id', $newOne->id)
            ->assertJsonPath('readings.1.id', $newTwo->id)
            ->assertJsonPath('latest_id', $newTwo->id);
    }

    public function test_dashboard_telemetry_readings_do_nothing_when_no_new_rows_exist(): void
    {
        $user = User::factory()->create();
        $device = Device::create(['device_id' => 'LEAF-ESP32-01', 'name' => 'ESP32-01', 'last_seen_at' => now()]);
        $latest = $device->telemetries()->create(['ph' => 6.1, 'measured_at' => now()]);

        $response = $this->actingAs($user)->getJson('/dashboard/telemetry/readings?device_id='.$device->id.'&after_id='.$latest->id);

        $response->assertOk()
            ->assertJsonPath('latest_id', $latest->id)
            ->assertJsonPath('latest_kpis', null)
            ->assertJsonCount(0, 'readings');
    }

    public function test_dashboard_telemetry_readings_filter_by_date_time_range(): void
    {
        $user = User::factory()->create();
        $device = Device::create(['device_id' => 'LEAF-RANGE-01', 'name' => 'Range Device', 'last_seen_at' => now()]);
        $before = $device->telemetries()->create(['ph' => 5.9, 'measured_at' => now()->setTime(8, 0)]);
        $inside = $device->telemetries()->create(['ph' => 6.4, 'measured_at' => now()->setTime(12, 0)]);
        $after = $device->telemetries()->create(['ph' => 7.1, 'measured_at' => now()->setTime(18, 0)]);

        $response = $this->actingAs($user)->getJson('/dashboard/telemetry/readings?device_id='.$device->id.'&from='.now()->format('Y-m-d').'T10:00&to='.now()->format('Y-m-d').'T14:00');

        $response->assertOk()
            ->assertJsonCount(1, 'readings')
            ->assertJsonPath('readings.0.id', $inside->id)
            ->assertJsonPath('latest_kpis.ph.value', '6.4');
        $this->assertNotSame($before->id, $response->json('readings.0.id'));
        $this->assertNotSame($after->id, $response->json('readings.0.id'));
    }

    public function test_dashboard_telemetry_readings_reject_invalid_date_range(): void
    {
        $user = User::factory()->create();
        $device = Device::create(['device_id' => 'LEAF-RANGE-02', 'name' => 'Range Device', 'last_seen_at' => now()]);

        $response = $this->actingAs($user)
            ->getJson('/dashboard/telemetry/readings?device_id='.$device->id.'&from=2026-08-25T14:00&to=2026-08-25T10:00');

        $this->assertSame(422, $response->status(), (string) $response->headers->get('Location'));
        $this->assertArrayHasKey('to', $response->json('errors'));
    }

    public function test_dashboard_telemetry_readings_return_empty_for_range_without_data(): void
    {
        $user = User::factory()->create();
        $device = Device::create(['device_id' => 'LEAF-RANGE-03', 'name' => 'Range Device', 'last_seen_at' => now()]);
        $device->telemetries()->create(['ph' => 6.4, 'measured_at' => now()->subDays(3)]);

        $this->actingAs($user)
            ->getJson('/dashboard/telemetry/readings?device_id='.$device->id.'&from='.now()->subDay()->format('Y-m-d\\TH:i').'&to='.now()->format('Y-m-d\\TH:i'))
            ->assertOk()
            ->assertJsonCount(0, 'readings')
            ->assertJsonPath('latest_kpis', null);
    }

    public function test_dashboard_telemetry_readings_accept_configured_visible_point_limit(): void
    {
        $user = User::factory()->create();
        $device = Device::create([
            'device_id' => 'LEAF-LIMIT-720',
            'name' => 'Limit Device',
            'last_seen_at' => now(),
        ]);

        foreach (range(1, 3) as $index) {
            $device->telemetries()->create([
                'ph' => 6 + ($index / 10),
                'measured_at' => now()->subSeconds(4 - $index),
            ]);
        }

        $configuredMaxPoints = (int) config('leaf.dashboard.live_chart.max_points');
        $this->assertSame(720, $configuredMaxPoints);

        $this->actingAs($user)
            ->getJson('/dashboard/telemetry/readings?device_id='.$device->id.'&limit=720')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'readings');
    }

    public function test_dashboard_telemetry_readings_reject_limit_above_configured_dashboard_max(): void
    {
        $user = User::factory()->create();
        $device = Device::create([
            'device_id' => 'LEAF-LIMIT-MAX',
            'name' => 'Limit Device',
            'last_seen_at' => now(),
        ]);

        $maxLimit = max(
            (int) config('leaf.dashboard.live_chart.max_points'),
            (int) config('leaf.dashboard.live_chart.buffer_points'),
            (int) config('leaf.dashboard.live_chart.poll_batch_limit'),
        );

        $response = $this->actingAs($user)
            ->getJson('/dashboard/telemetry/readings?device_id='.$device->id.'&limit='.($maxLimit + 1));

        $this->assertSame(422, $response->status());
        $this->assertArrayHasKey('limit', $response->json('errors'));
    }

    public function test_dashboard_telemetry_readings_honor_overridden_configured_max_points(): void
    {
        config([
            'leaf.dashboard.live_chart.max_points' => 800,
            'leaf.dashboard.live_chart.buffer_points' => 800,
            'leaf.dashboard.live_chart.poll_batch_limit' => 120,
        ]);

        $user = User::factory()->create();
        $device = Device::create([
            'device_id' => 'LEAF-LIMIT-CONFIG',
            'name' => 'Limit Device',
            'last_seen_at' => now(),
        ]);
        $device->telemetries()->create(['ph' => 6.2, 'measured_at' => now()]);

        $this->actingAs($user)
            ->getJson('/dashboard/telemetry/readings?device_id='.$device->id.'&limit=800')
            ->assertOk()
            ->assertJsonCount(1, 'readings');
    }
}
