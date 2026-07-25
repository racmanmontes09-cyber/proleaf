<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Device;

class DevicePolicy
{
    public function view(User $user, Device $device): bool
    {
        return $user->canPerform('devices.view');
    }

    public function create(User $user): bool
    {
        return $user->canPerform('devices.create');
    }

    public function update(User $user, Device $device): bool
    {
        return $user->canPerform('devices.update');
    }

    public function delete(User $user, Device $device): bool
    {
        return $user->canPerform('devices.delete');
    }
}
