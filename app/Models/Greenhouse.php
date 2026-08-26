<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Greenhouse extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_MAINTENANCE = 'maintenance';

    protected $fillable = [
        'name',
        'location',
        'user_id',
        'status',
    ];

    /**
     * The farmer / user assigned to this greenhouse.
     */
    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for farmer relation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The ESP32 / IoT devices assigned to this greenhouse.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Primary / latest assigned device for this greenhouse.
     */
    public function device(): HasOne
    {
        return $this->hasOne(Device::class)->latestOfMany();
    }

    /**
     * Telemetries stream through the assigned device(s).
     */
    public function telemetries(): HasManyThrough
    {
        return $this->hasManyThrough(Telemetry::class, Device::class);
    }

    /**
     * Activity logs for this greenhouse.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Alerts for this greenhouse.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Determine if this greenhouse is considered online based on its device.
     */
    public function getIsOnlineAttribute(): bool
    {
        $device = $this->device ?? $this->devices()->latest('last_seen_at')->first();

        return (bool) ($device?->is_online ?? false);
    }

    /**
     * Get the last seen timestamp from the assigned device.
     */
    public function getLastSeenAtAttribute(): ?\Carbon\Carbon
    {
        $device = $this->device ?? $this->devices()->latest('last_seen_at')->first();

        return $device?->last_seen_at;
    }
}
