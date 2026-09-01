<?php

namespace Tests\Feature;

use App\Console\Commands\MqttSubscribe;
use App\Models\Device;
use App\Services\DeviceStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActuatorStateFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // ── MQTT Status Parsing ───────────────────────────────────────────

    public function test_mqtt_status_message_updates_actuator_states(): void
    {
        $device = Device::create([
            'device_id' => 'ESP-ACT-01',
            'name' => 'Actuator Test Device',
            'last_seen_at' => now()->subMinutes(5),
        ]);

        $subscriber = app(MqttSubscribe::class);

        $payload = json_encode([
            'status' => 'online',
            'actuators' => [
                'cooling_fan' => true,
                'nutrient_pump_a' => false,
                'nutrient_pump_b' => true,
                'ph_up_pump' => false,
                'ph_down_pump' => true,
            ],
        ]);

        $subscriber->handleStatusMessage('leaf/devices/ESP-ACT-01/status', $payload);

        $device->refresh();

        $this->assertTrue($device->actuator_cooling_fan);
        $this->assertFalse($device->actuator_nutrient_pump_a);
        $this->assertTrue($device->actuator_nutrient_pump_b);
        $this->assertFalse($device->actuator_ph_up_pump);
        $this->assertTrue($device->actuator_ph_down_pump);
        $this->assertNotNull($device->actuator_states_updated_at);
    }

    public function test_mqtt_status_message_without_actuators_only_updates_last_seen(): void
    {
        $device = Device::create([
            'device_id' => 'ESP-ACT-02',
            'name' => 'Legacy Device',
            'last_seen_at' => now()->subMinutes(5),
            'actuator_cooling_fan' => true,
        ]);

        $subscriber = app(MqttSubscribe::class);

        $payload = json_encode(['status' => 'online']);

        $subscriber->handleStatusMessage('leaf/devices/ESP-ACT-02/status', $payload);

        $device->refresh();

        $this->assertTrue($device->actuator_cooling_fan, 'Legacy status message should not overwrite existing actuator state');
        $this->assertNull($device->actuator_states_updated_at);
    }

    public function test_mqtt_offline_status_does_not_update_actuator_states(): void
    {
        $device = Device::create([
            'device_id' => 'ESP-ACT-03',
            'actuator_cooling_fan' => true,
            'last_seen_at' => now(),
        ]);

        $subscriber = app(MqttSubscribe::class);

        $payload = json_encode(['status' => 'offline']);

        $subscriber->handleStatusMessage('leaf/devices/ESP-ACT-03/status', $payload);

        $device->refresh();

        $this->assertTrue($device->actuator_cooling_fan, 'Offline status should not clear actuator states');
    }

    public function test_mqtt_status_with_all_actuators_off(): void
    {
        $device = Device::create([
            'device_id' => 'ESP-ACT-04',
            'actuator_cooling_fan' => true,
            'actuator_nutrient_pump_a' => true,
            'actuator_nutrient_pump_b' => true,
            'actuator_ph_up_pump' => true,
            'actuator_ph_down_pump' => true,
            'last_seen_at' => now(),
        ]);

        $subscriber = app(MqttSubscribe::class);

        $payload = json_encode([
            'status' => 'online',
            'actuators' => [
                'cooling_fan' => false,
                'nutrient_pump_a' => false,
                'nutrient_pump_b' => false,
                'ph_up_pump' => false,
                'ph_down_pump' => false,
            ],
        ]);

        $subscriber->handleStatusMessage('leaf/devices/ESP-ACT-04/status', $payload);

        $device->refresh();

        $this->assertFalse($device->actuator_cooling_fan);
        $this->assertFalse($device->actuator_nutrient_pump_a);
        $this->assertFalse($device->actuator_nutrient_pump_b);
        $this->assertFalse($device->actuator_ph_up_pump);
        $this->assertFalse($device->actuator_ph_down_pump);
    }

    public function test_mqtt_status_with_partial_actuators(): void
    {
        $device = Device::create([
            'device_id' => 'ESP-ACT-05',
            'actuator_cooling_fan' => true,
            'actuator_ph_up_pump' => true,
            'last_seen_at' => now(),
        ]);

        $subscriber = app(MqttSubscribe::class);

        // Only cooling_fan in payload - other actuators should keep their previous state
        $payload = json_encode([
            'status' => 'online',
            'actuators' => [
                'cooling_fan' => false,
            ],
        ]);

        $subscriber->handleStatusMessage('leaf/devices/ESP-ACT-05/status', $payload);

        $device->refresh();

        $this->assertFalse($device->actuator_cooling_fan);
        $this->assertTrue($device->actuator_ph_up_pump, 'Unspecified actuators should retain previous state');
    }

    // ── Dashboard Actuator Cards ──────────────────────────────────────

    public function test_dashboard_actuator_cards_reflect_device_state(): void
    {
        $service = app(DeviceStatusService::class);

        $device = Device::create([
            'device_id' => 'ESP-DASH-01',
            'name' => 'Dashboard Test',
            'last_seen_at' => now(),
            'actuator_cooling_fan' => true,
            'actuator_nutrient_pump_a' => false,
            'actuator_nutrient_pump_b' => false,
            'actuator_ph_up_pump' => true,
            'actuator_ph_down_pump' => false,
        ]);

        $cards = $service->buildActuatorCards($device);

        $this->assertCount(5, $cards);
        $this->assertSame(['ph_up', 'cooling_fan', 'nutrient_a', 'nutrient_b', 'ph_down'], array_column($cards, 'command'));

        // Find the cooling fan card
        $fanCard = collect($cards)->firstWhere('command', 'cooling_fan');
        $this->assertEquals('ON', $fanCard['status']);
        $this->assertEquals('online', $fanCard['statusType']);

        // Find the pH Up card
        $phUpCard = collect($cards)->firstWhere('command', 'ph_up');
        $this->assertEquals('ON', $phUpCard['status']);
        $this->assertEquals('online', $phUpCard['statusType']);

        // Find the nutrient A card
        $nutACard = collect($cards)->firstWhere('command', 'nutrient_a');
        $this->assertEquals('OFF', $nutACard['status']);
        $this->assertEquals('standby', $nutACard['statusType']);

        // Find the pH Down card
        $phDownCard = collect($cards)->firstWhere('command', 'ph_down');
        $this->assertEquals('OFF', $phDownCard['status']);
        $this->assertEquals('standby', $phDownCard['statusType']);
    }

    public function test_dashboard_actuator_cards_group_on_before_off_and_preserve_group_order(): void
    {
        $service = app(DeviceStatusService::class);

        $device = Device::create([
            'device_id' => 'ESP-DASH-ORDER',
            'name' => 'Actuator Order Test',
            'last_seen_at' => now(),
            'actuator_nutrient_pump_a' => false,
            'actuator_nutrient_pump_b' => true,
            'actuator_ph_up_pump' => false,
            'actuator_ph_down_pump' => false,
            'actuator_cooling_fan' => true,
        ]);

        $cards = $service->buildActuatorCards($device);

        $this->assertSame(['nutrient_b', 'cooling_fan', 'nutrient_a', 'ph_up', 'ph_down'], array_column($cards, 'command'));
        $this->assertSame(['ON', 'ON', 'OFF', 'OFF', 'OFF'], array_column($cards, 'status'));
    }

    public function test_dashboard_actuator_on_count_updates_from_mqtt_status_refresh(): void
    {
        $device = Device::create([
            'device_id' => 'ESP-DASH-COUNT',
            'name' => 'Actuator Count Test',
            'last_seen_at' => now(),
            'actuator_nutrient_pump_a' => true,
            'actuator_cooling_fan' => true,
        ]);

        config(['leaf.dashboard.device_db_id' => $device->id]);

        $component = Livewire::test(\App\Livewire\Dashboard\DeviceStatus::class)
            ->assertSet('actuatorOnCount', 2)
            ->assertSee('2 ON');

        app(MqttSubscribe::class)->handleStatusMessage('leaf/devices/ESP-DASH-COUNT/status', json_encode([
            'status' => 'online',
            'actuators' => [
                'cooling_fan' => false,
                'nutrient_pump_a' => false,
                'nutrient_pump_b' => false,
                'ph_up_pump' => false,
                'ph_down_pump' => false,
            ],
        ]));

        $component->call('refreshDashboardLight')
            ->assertSet('actuatorOnCount', 0)
            ->assertSee('0 ON');
    }

    public function test_dashboard_actuator_cards_all_off_when_device_offline(): void
    {
        $service = app(DeviceStatusService::class);

        $device = Device::create([
            'device_id' => 'ESP-DASH-02',
            'name' => 'Offline Device',
            'last_seen_at' => now()->subMinutes(5),
            'actuator_cooling_fan' => true, // Stale state from before going offline
        ]);

        $cards = $service->buildActuatorCards($device);

        $fanCard = collect($cards)->firstWhere('command', 'cooling_fan');
        $this->assertEquals('OFF', $fanCard['status'], 'Offline device should show actuators as OFF');
        $this->assertEquals('standby', $fanCard['statusType']);
    }

    public function test_dashboard_actuator_cards_all_off_when_no_device(): void
    {
        $service = app(DeviceStatusService::class);

        $cards = $service->buildActuatorCards(null);

        $this->assertCount(5, $cards);

        foreach ($cards as $card) {
            $this->assertEquals('OFF', $card['status']);
            $this->assertEquals('standby', $card['statusType']);
        }
    }

    public function test_dashboard_actuator_cards_default_all_off(): void
    {
        $service = app(DeviceStatusService::class);

        $device = Device::create([
            'device_id' => 'ESP-DASH-03',
            'name' => 'New Device',
            'last_seen_at' => now(),
            // No actuator states set - should default to false
        ]);

        $cards = $service->buildActuatorCards($device);

        foreach ($cards as $card) {
            $this->assertEquals('OFF', $card['status']);
            $this->assertEquals('standby', $card['statusType']);
        }
    }

    // ── Dashboard Data Integration ────────────────────────────────────

    public function test_dashboard_data_includes_actuator_state_from_device(): void
    {
        $device = Device::create([
            'device_id' => 'ESP-FULL-01',
            'name' => 'Full Dashboard Test',
            'last_seen_at' => now(),
            'actuator_cooling_fan' => true,
        ]);

        \App\Models\Telemetry::create([
            'device_id' => $device->id,
            'air_temperature' => 25.0,
            'measured_at' => now(),
        ]);

        config(['leaf.dashboard.device_db_id' => $device->id]);

        $dashboardService = app(\App\Services\DashboardService::class);
        $data = $dashboardService->getDashboardData();

        $this->assertArrayHasKey('actuatorCards', $data);
        $this->assertCount(5, $data['actuatorCards']); // All 5 actuators now shown
        $this->assertSame(1, $data['actuatorOnCount']);

        $fanCard = collect($data['actuatorCards'])->firstWhere('command', 'cooling_fan');
        $this->assertNotNull($fanCard);
        $this->assertEquals('ON', $fanCard['status']);
        $this->assertEquals('online', $fanCard['statusType']);
    }

    // ── All 5 Actuators Coverage ──────────────────────────────────────

    public function test_all_five_actuators_reported_incorrect_order(): void
    {
        $service = app(DeviceStatusService::class);

        $device = Device::create([
            'device_id' => 'ESP-5ACT',
            'last_seen_at' => now(),
            'actuator_cooling_fan' => true,
            'actuator_nutrient_pump_a' => true,
            'actuator_nutrient_pump_b' => true,
            'actuator_ph_up_pump' => true,
            'actuator_ph_down_pump' => true,
        ]);

        $cards = $service->buildActuatorCards($device);

        $this->assertCount(5, $cards);

        $expectedCommands = ['nutrient_a', 'nutrient_b', 'ph_up', 'ph_down', 'cooling_fan'];
        $actualCommands = array_column($cards, 'command');
        $this->assertEquals($expectedCommands, $actualCommands);

        foreach ($cards as $card) {
            $this->assertEquals('ON', $card['status']);
            $this->assertEquals('online', $card['statusType']);
        }
    }

    // ── Device Model Defaults ─────────────────────────────────────────

    public function test_device_actuator_columns_default_to_false(): void
    {
        Device::create([
            'device_id' => 'ESP-DEFAULTS',
            'name' => 'Default Test',
        ]);

        // Re-read from database to verify defaults are applied
        $device = Device::where('device_id', 'ESP-DEFAULTS')->first();

        $this->assertFalse((bool) $device->actuator_cooling_fan);
        $this->assertFalse((bool) $device->actuator_nutrient_pump_a);
        $this->assertFalse((bool) $device->actuator_nutrient_pump_b);
        $this->assertFalse((bool) $device->actuator_ph_up_pump);
        $this->assertFalse((bool) $device->actuator_ph_down_pump);
        $this->assertNull($device->actuator_states_updated_at);
    }

    public function test_device_actuator_columns_are_mass_assignable(): void
    {
        $device = Device::create([
            'device_id' => 'ESP-MASS',
            'actuator_cooling_fan' => true,
            'actuator_nutrient_pump_a' => true,
        ]);

        $this->assertTrue($device->actuator_cooling_fan);
        $this->assertTrue($device->actuator_nutrient_pump_a);
    }
}
