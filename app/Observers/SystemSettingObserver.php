<?php

namespace App\Observers;

use App\Models\SystemSetting;
use App\Services\AuditService;

class SystemSettingObserver
{
    public function __construct(protected AuditService $audit)
    {
    }

    public function updated(SystemSetting $setting): void
    {
        $this->audit->log('settings.updated', ['model_type' => SystemSetting::class, 'model_id' => $setting->id, 'old' => $setting->getOriginal(), 'new' => $setting->getAttributes()]);
    }
}
