<?php

namespace App\Observers;

use App\Models\Alert;
use App\Services\AuditService;

class AlertObserver
{
    public function __construct(protected AuditService $audit)
    {
    }

    public function updated(Alert $alert): void
    {
        if ($alert->isDirty('acknowledged_at') || $alert->isDirty('resolved_at')) {
            $action = $alert->acknowledged_at ? 'alert.acknowledged' : ($alert->resolved_at ? 'alert.resolved' : 'alert.updated');
            $this->audit->log($action, ['model_type' => Alert::class, 'model_id' => $alert->id, 'old' => $alert->getOriginal(), 'new' => $alert->getAttributes(), 'device_id' => $alert->device_id]);
        }
    }
}
