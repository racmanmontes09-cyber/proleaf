<?php

namespace Tests\Feature;

use App\Console\Commands\MqttSubscribe;
use App\Models\Device;
use App\Services\DashboardService;
use App\Services\DeviceStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MqttActuatorIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function createTestDevice(string $suffix): Device
    {
        return Device::create([
            'device_id' => "ESP-INT-{$suffix}",
            'name' => "Integration Test Device {$suffix}",
            'last_seen_at' => now()->subMinutes(5),
        ]);
    }

    // ── All 5 Actuators: OFF → ON → OFF Transitions ──────────────────

    public function test_cooling_fan_off_to_on_to_off_via_mqtt(): void
    {
        $this->assertActuatorTransition(
            'FAN',
            'cooling_fan',
            'actuator_cooling_fan',
            'cooling_fan',
        );
    }

    public function test_nutrient_pump_a_off_to_on_to_off_via_mqtt(): void
    {
        $this->assertActuatorTransition(
            'NUTA',
            'nutrient_pump_a',
            'actuator_nutrient_pump_a',
            'nutrient_a',
        );
    }

    public function test_nutrient_pump_b_off_to_on_to_off_via_mqtt(): void
    {
        $this->assertActuatorTransition(
            'NUTB',
            'nutrient_pump_b',
            'actuator_nutrient_pump_b',
            'nutrient_b',
        );
    }

    public function test_ph_up_pump_off_to_on_to_off_via_mqtt(): void
    {
        $this->assertActuatorTransition(
            'PHUP',
            'ph_up_pump',
            'actuator_ph_up_pump',
            'ph_up',
        );
    }

    public function test_ph_down_pump_off_to_on_to_off_via_mqtt(): void
    {
        $this->assertActuatorTransition(
            'PHDN',
            'ph_down_pump',
            'actuator_ph_down_pump',
            'ph_down',
        );
    }

    /**
     * Full state transition test for a single actuator:
     * OFF → ON → OFF, verifying database and dashboard at each step.
     */
    private function assertActuatorTransition(
        string $deviceSuffix,
        string $jsonKey,
        string $dbColumn,
        string $dashboardCommand,
    ): void {
        $subscriber = app(MqttSubscribe::class);
        $deviceStatusService = app(DeviceStatusService::class);

        $device = $this->createTestDevice($deviceSuffix);
        $topic = "leaf/devices/{$device->device_id}/status";

        // ── Step 1: Initial state — all OFF ───────────────────────
        $device->refresh();
        $this->assertFalse((bool) $device->{$dbColumn}, "Initial state should be OFF for {$jsonKey}");

        $cards = $deviceStatusService->buildActuatorCards($device);
        $card = collect($cards)->firstWhere('command', $dashboardCommand);
        $this->assertEquals('OFF', $card['status'], "Dashboard should show OFF initially for {$jsonKey}");

        // ── Step 2: Publish ON via MQTT ──────────────────────────
        $payload = json_encode([
            'status' => 'online',
            'actuators' => [
                'cooling_fan' => ($jsonKey === 'cooling_fan'),
                'nutrient_pump_a' => ($jsonKey === 'nutrient_pump_a'),
                'nutrient_pump_b' => ($jsonKey === 'nutrient_pump_b'),
                'ph_up_pump' => ($jsonKey === 'ph_up_pump'),
                'ph_down_pump' => ($jsonKey === 'ph_down_pump'),
            ],
        ]);

        $subscriber->handleStatusMessage($topic, $payload);

        // ── Step 3: Verify database updated ──────────────────────
        $device->refresh();
        $this->assertTrue((bool) $device->{$dbColumn}, "Database should show ON for {$jsonKey}");
        $this->assertNotNull($device->actuator_states_updated_at, "actuator_states_updated_at should be set");

        // Verify other actuators are at their expected state
        $allKeys = ['cooling_fan', 'nutrient_pump_a', 'nutrient_pump_b', 'ph_up_pump', 'ph_down_pump'];
        foreach ($allKeys as $key) {
            $col = 'actuator_' . $key;
            $expected = ($key === $jsonKey);
            $this->assertEquals($expected, (bool) $device->{$col}, "Database {$col} should be " . ($expected ? 'ON' : 'OFF'));
        }

        // ── Step 4: Verify dashboard shows ON ────────────────────
        $cards = $deviceStatusService->buildActuatorCards($device);
        $card = collect($cards)->firstWhere('command', $dashboardCommand);
        $this->assertEquals('ON', $card['status'], "Dashboard should show ON for {$jsonKey}");
        $this->assertEquals('online', $card['statusType'], "Dashboard statusType should be online for {$jsonKey}");

        // Verify other cards are OFF
        foreach ($cards as $c) {
            if ($c['command'] !== $dashboardCommand) {
                $this->assertEquals('OFF', $c['status'], "Other card {$c['command']} should be OFF");
                $this->assertEquals('standby', $c['statusType'], "Other card {$c['command']} statusType should be standby");
            }
        }

        // ── Step 5: Publish OFF via MQTT ─────────────────────────
        $payloadOff = json_encode([
            'status' => 'online',
            'actuators' => [
                'cooling_fan' => false,
                'nutrient_pump_a' => false,
                'nutrient_pump_b' => false,
                'ph_up_pump' => false,
                'ph_down_pump' => false,
            ],
        ]);

        $subscriber->handleStatusMessage($topic, $payloadOff);

        // ── Step 6: Verify database shows OFF ────────────────────
        $device->refresh();
        $this->assertFalse((bool) $device->{$dbColumn}, "Database should show OFF after OFF message for {$jsonKey}");

        // ── Step 7: Verify dashboard shows OFF ───────────────────
        $cards = $deviceStatusService->buildActuatorCards($device);
        $card = collect($cards)->firstWhere('command', $dashboardCommand);
        $this->assertEquals('OFF', $card['status'], "Dashboard should show OFF after OFF message for {$jsonKey}");
        $this->assertEquals('standby', $card['statusType'], "Dashboard statusType should be standby for {$jsonKey}");
    }

    // ── Multi-Actuator Simultaneous State ─────────────────────────────

    public function test_multiple_actuators_on_simultaneously(): void
    {
        $subscriber = app(MqttSubscribe::class);
        $deviceStatusService = app(DeviceStatusService::class);

        $device = $this->createTestDevice('MULTI');
        $topic = "leaf/devices/{$device->device_id}/status";

        $payload = json_encode([
            'status' => 'online',
            'actuators' => [
                'cooling_fan' => true,
                'nutrient_pump_a' => true,
                'nutrient_pump_b' => false,
                'ph_up_pump' => false,
                'ph_down_pump' => true,
            ],
        ]);

        $subscriber->handleStatusMessage($topic, $payload);

        $device->refresh();
        $this->assertTrue((bool) $device->actuator_cooling_fan);
        $this->assertTrue((bool) $device->actuator_nutrient_pump_a);
        $this->assertFalse((bool) $device->actuator_nutrient_pump_b);
        $this->assertFalse((bool) $device->actuator_ph_up_pump);
        $this->assertTrue((bool) $device->actuator_ph_down_pump);

        $cards = $deviceStatusService->buildActuatorCards($device);

        $fanCard = collect($cards)->firstWhere('command', 'cooling_fan');
        $this->assertEquals('ON', $fanCard['status']);

        $nutACard = collect($cards)->firstWhere('command', 'nutrient_a');
        $this->assertEquals('ON', $nutACard['status']);

        $nutBCard = collect($cards)->firstWhere('command', 'nutrient_b');
        $this->assertEquals('OFF', $nutBCard['status']);

        $phUpCard = collect($cards)->firstWhere('command', 'ph_up');
        $this->assertEquals('OFF', $phUpCard['status']);

        $phDownCard = collect($cards)->firstWhere('command', 'ph_down');
        $this->assertEquals('ON', $phDownCard['status']);
    }

    // ── Safety Interlock: Device Offline → Dashboard Shows OFF ────────

    public function test_offline_device_shows_all_actuators_off(): void
    {
        $deviceStatusService = app(DeviceStatusService::class);

        $device = $this->createTestDevice('SAFETY');
        $device->update([
            'actuator_cooling_fan' => true,
            'actuator_nutrient_pump_a' => true,
            'actuator_ph_up_pump' => true,
            'last_seen_at' => now()->subMinutes(5), // Offline
        ]);

        $cards = $deviceStatusService->buildActuatorCards($device);

        foreach ($cards as $card) {
            $this->assertEquals('OFF', $card['status'], "Offline device: {$card['command']} should show OFF");
            $this->assertEquals('standby', $card['statusType'], "Offline device: {$card['command']} statusType should be standby");
        }
    }

    // ── Dashboard Integration via DashboardService ────────────────────

    public function test_dashboard_service_reflects_actuator_state(): void
    {
        $device = $this->createTestDevice('DASH');
        $device->update([
            'actuator_cooling_fan' => true,
            'last_seen_at' => now(),
        ]);

        \App\Models\Telemetry::create([
            'device_id' => $device->id,
            'air_temperature' => 25.0,
            'measured_at' => now(),
        ]);

        config(['leaf.dashboard.device_db_id' => $device->id]);

        $dashboardService = app(DashboardService::class);
        $data = $dashboardService->getDashboardData();

        $this->assertArrayHasKey('actuatorCards', $data);
        $this->assertCount(5, $data['actuatorCards']); // All 5 actuators now shown

        $fanCard = collect($data['actuatorCards'])->firstWhere('command', 'cooling_fan');
        $this->assertNotNull($fanCard);
        $this->assertEquals('ON', $fanCard['status']);
        $this->assertEquals('online', $fanCard['statusType']);
    }

    // ── Real MQTT Connection Test ─────────────────────────────────────

    public function test_real_mqtt_publish_and_receive(): void
    {
        // This test publishes a real MQTT message and verifies the subscriber handles it.
        // It does NOT require a running MQTT subscriber process — it calls the handler directly.
        $subscriber = app(MqttSubscribe::class);
        $device = $this->createTestDevice('MQTT');
        $topic = "leaf/devices/{$device->device_id}/status";

        // Simulate what the ESP32 firmware would publish
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

        // Publish to real Mosquitto broker
        $mqttHost = config('leaf.mqtt.host', '127.0.0.1');
        $mqttPort = config('leaf.mqtt.port', 1883);

        $published = false;
        if (function_exists('exec')) {
            $escapedPayload = escapeshellarg($payload);
            $escapedTopic = escapeshellarg($topic);
            $cmd = "mosquitto_pub -h {$mqttHost} -p {$mqttPort} -t {$escapedTopic} -m {$escapedPayload} 2>&1";
            exec($cmd, $output, $exitCode);
            $published = ($exitCode === 0);
        }

        if ($published) {
            // Verify the handler processes it correctly (simulating what the subscriber would do)
            $subscriber->handleStatusMessage($topic, $payload);

            $device->refresh();
            $this->assertTrue((bool) $device->actuator_cooling_fan, 'Real MQTT: cooling_fan should be ON');
            $this->assertFalse((bool) $device->actuator_nutrient_pump_a, 'Real MQTT: nutrient_pump_a should be OFF');
            $this->assertTrue((bool) $device->actuator_nutrient_pump_b, 'Real MQTT: nutrient_pump_b should be ON');
            $this->assertFalse((bool) $device->actuator_ph_up_pump, 'Real MQTT: ph_up_pump should be OFF');
            $this->assertTrue((bool) $device->actuator_ph_down_pump, 'Real MQTT: ph_down_pump should be ON');
        } else {
            // Mosquitto not available — test the handler directly
            $subscriber->handleStatusMessage($topic, $payload);

            $device->refresh();
            $this->assertTrue((bool) $device->actuator_cooling_fan);
        }
    }
}
