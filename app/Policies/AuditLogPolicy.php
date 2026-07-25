<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AuditLog;

class AuditLogPolicy
{
    public function view(User $user): bool
    {
        return $user->canPerform('audit.view');
    }
}
