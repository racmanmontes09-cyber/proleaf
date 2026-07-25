<?php

namespace App\Providers;

use App\Models\Device;
use App\Models\User;
use App\Models\DeviceCommand;
use App\Models\Telemetry;
use App\Models\Alert;
use App\Models\SystemSetting;
use App\Observers\UserObserver;
use App\Observers\DeviceObserver;
use App\Observers\DeviceCommandObserver;
use App\Observers\TelemetryObserver;
use App\Observers\AlertObserver;
use App\Observers\SystemSettingObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Services\AuditService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('device-heartbeat', function (Request $request) {
            return Limit::perMinute((int) config('leaf.device_api.rate_limits.heartbeat_per_minute', 60))
                ->by($this->deviceRateLimitKey($request));
        });

        RateLimiter::for('device-telemetry', function (Request $request) {
            return Limit::perMinute((int) config('leaf.device_api.rate_limits.telemetry_per_minute', 120))
                ->by($this->deviceRateLimitKey($request));
        });

        RateLimiter::for('device-commands', function (Request $request) {
            return Limit::perMinute((int) config('leaf.device_api.rate_limits.commands_per_minute', 60))
                ->by($this->deviceRateLimitKey($request));
        });
        RateLimiter::for('device-commands', function (Request $request) {
            return Limit::perMinute((int) config('leaf.device_api.rate_limits.commands_per_minute', 60))
                ->by($this->deviceRateLimitKey($request));
        });

        // register model observers so audit events are created
        User::observe(app(UserObserver::class));
        Device::observe(app(DeviceObserver::class));
        DeviceCommand::observe(app(DeviceCommandObserver::class));
        Telemetry::observe(app(TelemetryObserver::class));
        Alert::observe(app(AlertObserver::class));
        SystemSetting::observe(app(SystemSettingObserver::class));

        // Listen for login/logout events to record audit entries
        Event::listen(Login::class, function (Login $event) {
            app(AuditService::class)->log('user.logged_in', ['user_id' => $event->user->id]);
        });

        Event::listen(Logout::class, function (Logout $event) {
            $userId = $event->user?->id ?? null;
            app(AuditService::class)->log('user.logged_out', ['user_id' => $userId]);
        });
    }

    public function register(): void
    {
        // Intentionally left empty
    }

    private function deviceRateLimitKey(Request $request): string
    {
        $device = $request->attributes->get('device');

        if ($device instanceof Device) {
            return 'device:'.$device->getKey();
        }

        return 'ip:'.$request->ip();
    }
}
