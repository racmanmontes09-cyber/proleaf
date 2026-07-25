<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AuditService;

class UserObserver
{
    public function __construct(protected AuditService $audit)
    {
    }

    public function created(User $user): void
    {
        $this->audit->log('user.created', ['model_type' => User::class, 'model_id' => $user->id, 'new' => $user->toArray()]);
    }

    public function updated(User $user): void
    {
        $this->audit->log('user.updated', ['model_type' => User::class, 'model_id' => $user->id, 'old' => $user->getOriginal(), 'new' => $user->getAttributes()]);
    }

    public function deleted(User $user): void
    {
        $this->audit->log('user.deleted', ['model_type' => User::class, 'model_id' => $user->id, 'old' => $user->getOriginal()]);
    }
}
