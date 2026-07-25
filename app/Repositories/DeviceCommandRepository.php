<?php

namespace App\Repositories;

use App\Models\Device;
use App\Models\DeviceCommand;
use Illuminate\Support\Collection;

class DeviceCommandRepository
{
    public function create(array $attributes): DeviceCommand
    {
        return DeviceCommand::create($attributes);
    }

    public function pendingForDevice(Device $device, int $limit = 20): Collection
    {
        return DeviceCommand::query()
            ->forDevice($device)
            ->pending()
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    public function activeForDevice(Device $device, int $limit = 50): Collection
    {
        return DeviceCommand::query()
            ->forDevice($device)
            ->active()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function findDuplicatePending(Device $device, string $command, array $payload): ?DeviceCommand
    {
        $normalizedPayload = $this->normalizePayload($payload);

        return DeviceCommand::query()
            ->forDevice($device)
            ->pending()
            ->where('command', $command)
            ->get()
            ->first(fn (DeviceCommand $commandModel): bool => $this->normalizePayload($commandModel->payload) === $normalizedPayload);
    }

    public function forDeviceById(Device $device, int $id): ?DeviceCommand
    {
        return DeviceCommand::query()
            ->forDevice($device)
            ->where('id', $id)
            ->first();
    }

    public function save(DeviceCommand $deviceCommand): DeviceCommand
    {
        $deviceCommand->save();

        return $deviceCommand;
    }

    public function expire(DeviceCommand $deviceCommand): DeviceCommand
    {
        $deviceCommand->status = DeviceCommand::STATUS_EXPIRED;
        $deviceCommand->save();

        return $deviceCommand;
    }

    protected function normalizePayload(array $payload): string
    {
        ksort($payload);
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
