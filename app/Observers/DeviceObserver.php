<?php

namespace App\Observers;

use App\Models\Device;
use App\Services\AuditService;

class DeviceObserver
{
    public function __construct(protected AuditService $audit)
    {
    }

    public function created(Device $device): void
    {
        $this->audit->log('device.created', ['model_type' => Device::class, 'model_id' => $device->id, 'new' => $device->toArray()]);
    }

    public function updated(Device $device): void
    {
        $this->audit->log('device.updated', ['model_type' => Device::class, 'model_id' => $device->id, 'old' => $device->getOriginal(), 'new' => $device->getAttributes()]);
    }

    public function deleted(Device $device): void
    {
        $this->audit->log('device.deleted', ['model_type' => Device::class, 'model_id' => $device->id, 'old' => $device->getOriginal()]);
    }
}
