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
            ->assertRedirect('/login');
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
}
