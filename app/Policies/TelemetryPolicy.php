<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Telemetry;

class TelemetryPolicy
{
    public function view(User $user, Telemetry $telemetry): bool
    {
        return $user->canPerform('telemetry.view');
    }

}
