<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LeafSimulateCommandTest extends TestCase
{
    public function test_simulator_sends_telemetry_to_the_existing_api_contract(): void
    {
        config()->set('leaf.simulation.device_token', 'leaf_test_token');

        Http::fake([
            '*' => Http::response(['success' => true, 'message' => 'ok'], 201),
        ]);

        $this->artisan('leaf:simulate', [
            '--devices' => 2,
            '--interval' => 0,
            '--duration' => 0,
        ])->assertExitCode(0);

        Http::assertSentCount(2);

        Http::assertSent(function ($request): bool {
            $this->assertSame('POST', $request->method());
            $this->assertStringEndsWith('/api/devices/telemetry', $request->url());

            $payload = $request->data();
            $this->assertArrayHasKey('device_id', $payload);
            $this->assertArrayHasKey('air_temperature', $payload);
            $this->assertArrayHasKey('humidity', $payload);
            $this->assertArrayHasKey('water_temperature', $payload);
            $this->assertArrayHasKey('ph', $payload);
            $this->assertArrayHasKey('ec', $payload);
            $this->assertArrayHasKey('water_flow', $payload);
            $this->assertArrayHasKey('water_level', $payload);
            $this->assertArrayHasKey('signal_strength', $payload);
            $this->assertArrayHasKey('battery_voltage', $payload);
            $this->assertArrayHasKey('firmware_version', $payload);
            $this->assertArrayHasKey('payload_version', $payload);
            $this->assertArrayHasKey('sequence_number', $payload);
            $this->assertArrayHasKey('measured_at', $payload);

            return true;
        });
    }
}
