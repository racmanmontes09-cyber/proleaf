<?php

namespace App\Observers;

use App\Models\Telemetry;
use App\Services\AuditService;

class TelemetryObserver
{
    public function __construct(protected AuditService $audit)
    {
    }

    public function deleted(Telemetry $telemetry): void
    {
        $this->audit->log('telemetry.deleted', ['model_type' => Telemetry::class, 'model_id' => $telemetry->id, 'old' => $telemetry->getOriginal(), 'device_id' => $telemetry->device_id]);
    }
}
