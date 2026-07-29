<?php

namespace App\Providers;

use App\Console\Commands\SimulateLeafTelemetry;
use App\Models\Device;
use App\Models\User;
use App\Models\DeviceCommand;
use App\Models\Telemetry;
use App\Models\Alert;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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

    }

    public function register(): void
    {
        $this->commands([
            SimulateLeafTelemetry::class,
        ]);
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
