<?php

namespace App\Listeners;

use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function handle(Logout $event): void
    {
        if ($event->user instanceof \App\Models\User) {
            ActivityLogger::log(
                action: 'Logout',
                target: 'Web Application',
                details: ['email' => $event->user->email, 'guard' => $event->guard],
                user: $event->user
            );
        }
    }
}
