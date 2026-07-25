<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DeviceCommand;

class DeviceCommandPolicy
{
    public function issue(User $user): bool
    {
        return $user->canPerform('commands.issue');
    }

    public function cancel(User $user, DeviceCommand $command): bool
    {
        return $user->canPerform('commands.cancel');
    }
}
