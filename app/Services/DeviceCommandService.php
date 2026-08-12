<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Repositories\DeviceCommandRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class DeviceCommandService
{
    public const SUPPORTED_FIRMWARE_COMMANDS = [
        'cooling_fan',
        'water_pump',
        'nutrient_a',
        'nutrient_pump_a',
        'nutrient_b',
        'nutrient_pump_b',
        'ph_up',
        'ph_up_pump',
        'ph_down',
        'ph_down_pump',
    ];

    public function __construct(protected DeviceCommandRepository $commandRepository)
    {
    }

    public function queueCommand(
        Device $device,
        string $command,
        array $payload = [],
        ?string $title = null,
        int $maxAttempts = 3,
        ?Carbon $expiresAt = null,
        array $metadata = []
    ): DeviceCommand {
        if (! in_array($command, self::SUPPORTED_FIRMWARE_COMMANDS, true)) {
            throw new InvalidArgumentException("Unsupported firmware command: {$command}");
        }

        $duplicate = $this->commandRepository->findDuplicatePending($device, $command, $payload);

        if ($duplicate !== null) {
            return $duplicate;
        }

        return $this->commandRepository->create([
            'device_id' => $device->id,
            'command' => $command,
            'title' => $title ?? $this->formatCommandTitle($command),
            'payload' => $payload,
            'status' => DeviceCommand::STATUS_PENDING,
            'attempt_count' => 0,
            'max_attempts' => $maxAttempts,
            'expires_at' => $expiresAt,
            'metadata' => $metadata,
        ]);
    }

    public function getPendingCommands(Device $device, int $limit = 20): Collection
    {
        return $this->commandRepository->pendingForDevice($device)->filter(function (DeviceCommand $command) {
            return $this->commandHasNotExpired($command);
        })->values();
    }

    public function getPendingCommandCount(Device $device): int
    {
        return $this->getPendingCommands($device)->count();
    }

    public function claimPendingCommands(Device $device, int $limit = 20): Collection
    {
        $commands = $this->getPendingCommands($device, $limit);

        return $commands->map(function (DeviceCommand $command) {
            return $this->markCommandDelivered($command);
        });
    }

    public function markCommandDelivered(DeviceCommand $command): DeviceCommand
    {
        if ($command->status !== DeviceCommand::STATUS_PENDING) {
            return $command;
        }

        $command->status = DeviceCommand::STATUS_DELIVERED;
        $command->delivered_at = now();
        $command->attempt_count = max($command->attempt_count, 0) + 1;
        $this->commandRepository->save($command);

        return $command;
    }

    public function markCommandCompleted(DeviceCommand $command, array $result = []): DeviceCommand
    {
        $command->status = DeviceCommand::STATUS_COMPLETED;
        $command->executed_at = now();
        $command->result = $result;
        $this->commandRepository->save($command);

        return $command;
    }

    public function markCommandFailed(DeviceCommand $command, string $failureReason): DeviceCommand
    {
        $command->status = DeviceCommand::STATUS_FAILED;
        $command->executed_at = now();
        $command->failure_reason = $failureReason;
        $this->commandRepository->save($command);

        return $command;
    }

    public function expireStaleCommands(): int
    {
        $expired = DeviceCommand::query()
            ->where('status', DeviceCommand::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expired as $command) {
            $this->commandRepository->expire($command);
        }

        return $expired->count();
    }

    protected function formatCommandTitle(string $command): string
    {
        return ucwords(str_replace('_', ' ', $command));
    }

    protected function commandHasNotExpired(DeviceCommand $command): bool
    {
        if ($command->expires_at === null) {
            return true;
        }

        return $command->expires_at->isFuture();
    }
}
