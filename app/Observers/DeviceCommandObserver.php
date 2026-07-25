<?php

namespace App\Observers;

use App\Models\DeviceCommand;
use App\Services\AuditService;

class DeviceCommandObserver
{
    public function __construct(protected AuditService $audit)
    {
    }

    public function created(DeviceCommand $command): void
    {
        $this->audit->log('command.issued', ['model_type' => DeviceCommand::class, 'model_id' => $command->id, 'new' => $command->toArray(), 'device_id' => $command->device_id]);
    }

    public function updated(DeviceCommand $command): void
    {
        $this->audit->log('command.updated', ['model_type' => DeviceCommand::class, 'model_id' => $command->id, 'old' => $command->getOriginal(), 'new' => $command->getAttributes(), 'device_id' => $command->device_id]);
    }

    public function deleted(DeviceCommand $command): void
    {
        $this->audit->log('command.deleted', ['model_type' => DeviceCommand::class, 'model_id' => $command->id, 'old' => $command->getOriginal(), 'device_id' => $command->device_id]);
    }
}
