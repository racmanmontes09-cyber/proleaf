<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Alert;

class AlertPolicy
{
    public function view(User $user): bool
    {
        return $user->canPerform('alerts.view');
    }

    public function acknowledge(User $user): bool
    {
        return $user->canPerform('alerts.acknowledge');
    }

    public function delete(User $user): bool
    {
        return $user->canPerform('alerts.delete');
    }
}
