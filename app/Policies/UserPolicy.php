<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $user): bool
    {
        return $user->canPerform('users.view');
    }

    public function create(User $user): bool
    {
        return $user->canPerform('users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->canPerform('users.update');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->canPerform('users.delete');
    }
}
