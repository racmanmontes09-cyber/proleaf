<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'device_id',
        'name',
        'firmware_version',
        'local_ip_address',
        'wifi_rssi',
        'uptime_seconds',
        'free_heap',
        'last_boot_at',
        'last_seen_at',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'last_boot_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Determine if the device is currently online.
     */
    public function getIsOnlineAttribute(): bool
    {
        if (! $this->last_seen_at) {
            return false;
        }

        return $this->last_seen_at->diffInSeconds(now()) <= 30;
    }
}