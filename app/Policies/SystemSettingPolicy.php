<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SystemSetting;

class SystemSettingPolicy
{
    public function view(User $user): bool
    {
        return $user->canPerform('settings.view');
    }

    public function update(User $user): bool
    {
        return $user->canPerform('settings.update');
    }
}
