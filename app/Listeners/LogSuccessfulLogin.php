<?php

namespace App\Listeners;

use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        if ($event->user instanceof \App\Models\User) {
            ActivityLogger::log(
                action: 'Login',
                target: 'Web Application',
                details: ['email' => $event->user->email, 'guard' => $event->guard],
                user: $event->user
            );
        }
    }
}
