<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Device;
use App\Models\Greenhouse;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log a user or system activity.
     */
    public static function log(
        string $action,
        ?string $target = null,
        array $details = [],
        ?User $user = null,
        ?Greenhouse $greenhouse = null,
        ?Device $device = null
    ): ActivityLog {
        $userId = $user?->id ?? Auth::id();
        $ip = Request::ip();
        $userAgent = Request::header('User-Agent');

        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'target' => $target,
            'greenhouse_id' => $greenhouse?->id,
            'device_id' => $device?->id,
            'ip_address' => $ip,
            'user_agent' => is_string($userAgent) ? substr($userAgent, 0, 500) : null,
            'details' => $details !== [] ? $details : null,
        ]);
    }
}
