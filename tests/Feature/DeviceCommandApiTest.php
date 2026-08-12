<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Services\DeviceCommandService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DeviceCommandApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_polling_requires_device_authentication(): void
    {
        $this->getJson('/api/devices/commands')
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Missing device token.',
            ]);
    }

    public function test_authenticated_device_can_only_poll_its_own_pending_commands(): void
    {
        [$device, $token] = $this->createAuthenticatedDevice([
            'device_id' => 'LEAF-CMD-01',
        ]);
        [$otherDevice] = $this->createAuthenticatedDevice([
            'device_id' => 'LEAF-CMD-02',
        ]);

        $ownCommand = DeviceCommand::create([
            'device_id' => $device->id,
            'command' => 'water_pump',
            'title' => 'Water Pump',
            'payload' => [],
            'status' => DeviceCommand::STATUS_PENDING,
            'attempt_count' => 0,
            'max_attempts' => 3,
        ]);

        $otherCommand = DeviceCommand::create([
            'device_id' => $otherDevice->id,
            'command' => 'ph_up',
            'title' => 'pH Up',
            'payload' => [],
            'status' => DeviceCommand::STATUS_PENDING,
            'attempt_count' => 0,
            'max_attempts' => 3,
        ]);

        $this->withToken($token)->getJson('/api/devices/commands')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.commands')
            ->assertJsonPath('data.commands.0.id', $ownCommand->id)
            ->assertJsonPath('data.commands.0.command', 'water_pump')
            ->assertJsonPath('data.commands.0.status', DeviceCommand::STATUS_DELIVERED);

        $this->assertDatabaseHas('device_commands', [
            'id' => $ownCommand->id,
            'status' => DeviceCommand::STATUS_DELIVERED,
            'attempt_count' => 1,
        ]);
        $this->assertDatabaseHas('device_commands', [
            'id' => $otherCommand->id,
            'status' => DeviceCommand::STATUS_PENDING,
            'attempt_count' => 0,
        ]);
    }

    public function test_delivered_command_is_not_returned_on_second_poll(): void
    {
        [$device, $token] = $this->createAuthenticatedDevice();

        DeviceCommand::create([
            'device_id' => $device->id,
            'command' => 'ph_down',
            'title' => 'pH Down',
            'payload' => [],
            'status' => DeviceCommand::STATUS_PENDING,
            'attempt_count' => 0,
            'max_attempts' => 3,
        ]);

        $this->withToken($token)->getJson('/api/devices/commands')
            ->assertOk()
            ->assertJsonCount(1, 'data.commands');

        $this->withToken($token)->getJson('/api/devices/commands')
            ->assertOk()
            ->assertJsonCount(0, 'data.commands');
    }

    public function test_device_cannot_acknowledge_command_for_another_device(): void
    {
        [, $token] = $this->createAuthenticatedDevice([
            'device_id' => 'LEAF-CMD-01',
        ]);
        [$otherDevice] = $this->createAuthenticatedDevice([
            'device_id' => 'LEAF-CMD-02',
        ]);

        $otherCommand = DeviceCommand::create([
            'device_id' => $otherDevice->id,
            'command' => 'water_pump',
            'title' => 'Water Pump',
            'payload' => [],
            'status' => DeviceCommand::STATUS_DELIVERED,
            'attempt_count' => 1,
            'max_attempts' => 3,
        ]);

        $this->withToken($token)->patchJson('/api/devices/commands/'.$otherCommand->id, [
            'status' => 'completed',
            'result' => ['message' => 'done'],
        ])
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Command not found for this device.',
            ]);

        $this->assertDatabaseHas('device_commands', [
            'id' => $otherCommand->id,
            'status' => DeviceCommand::STATUS_DELIVERED,
        ]);
    }

    public function test_command_acknowledgment_validates_status_and_result_shape(): void
    {
        [$device, $token] = $this->createAuthenticatedDevice();

        $command = DeviceCommand::create([
            'device_id' => $device->id,
            'command' => 'cooling_fan',
            'title' => 'Cooling Fan',
            'payload' => [],
            'status' => DeviceCommand::STATUS_DELIVERED,
            'attempt_count' => 1,
            'max_attempts' => 3,
        ]);

        $this->withToken($token)->patchJson('/api/devices/commands/'.$command->id, [
            'status' => 'replayed',
            'result' => 'not-an-array',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'result']);
    }

    public function test_device_can_acknowledge_own_command_successfully(): void
    {
        [$device, $token] = $this->createAuthenticatedDevice();

        $command = DeviceCommand::create([
            'device_id' => $device->id,
            'command' => 'cooling_fan',
            'title' => 'Cooling Fan',
            'payload' => [],
            'status' => DeviceCommand::STATUS_DELIVERED,
            'attempt_count' => 1,
            'max_attempts' => 3,
        ]);

        $this->withToken($token)->patchJson('/api/devices/commands/'.$command->id, [
            'status' => 'completed',
            'result' => ['message' => 'Command applied'],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $command->id)
            ->assertJsonPath('data.status', DeviceCommand::STATUS_COMPLETED);

        $this->assertDatabaseHas('device_commands', [
            'id' => $command->id,
            'status' => DeviceCommand::STATUS_COMPLETED,
        ]);
    }


    public function test_command_service_rejects_unsupported_firmware_command(): void
    {
        [$device] = $this->createAuthenticatedDevice();

        $this->expectException(InvalidArgumentException::class);

        app(DeviceCommandService::class)->queueCommand($device, 'restart_node');
    }

    private function createAuthenticatedDevice(array $attributes = []): array
    {
        $device = Device::create(array_merge([
            'device_id' => 'LEAF-CMD-DEVICE',
            'name' => 'Command Test Device',
        ], $attributes));

        $token = $device->issueDeviceToken();

        return [$device->refresh(), $token];
    }
}
