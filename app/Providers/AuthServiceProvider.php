<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\SystemSetting;
use App\Models\Telemetry;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\DeviceCommandPolicy;
use App\Policies\DevicePolicy;
use App\Policies\SystemSettingPolicy;
use App\Policies\TelemetryPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Device::class => DevicePolicy::class,
        Telemetry::class => TelemetryPolicy::class,
        DeviceCommand::class => DeviceCommandPolicy::class,
        SystemSetting::class => SystemSettingPolicy::class,
        User::class => UserPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasRole') && $user->hasRole(config('rbac.super_admin_role', 'super-admin'))) {
                return true;
            }
        });
    }
}
