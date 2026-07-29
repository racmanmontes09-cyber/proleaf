<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\DeviceCommand;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Device extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DISABLED = 'disabled';

    public const STATUS_REVOKED = 'revoked';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'device_id',
        'name',
        'status',
        'device_token_hash',
        'device_token_expires_at',
        'device_token_last_used_at',
        'device_token_revoked_at',
        'firmware_version',
        'local_ip_address',
        'wifi_rssi',
        'uptime_seconds',
        'free_heap',
        'last_boot_at',
        'last_seen_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'device_token_hash',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'device_token_expires_at' => 'datetime',
        'device_token_last_used_at' => 'datetime',
        'device_token_revoked_at' => 'datetime',
        'last_boot_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Device $device): void {
            $device->uuid ??= (string) Str::uuid();
            $device->status ??= self::STATUS_ACTIVE;
        });
    }

    public function telemetries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Telemetry::class);
    }

    public function deviceCommands(): HasMany
    {
        return $this->hasMany(DeviceCommand::class);
    }

    public function latestTelemetry(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Telemetry::class)
            ->orderByDesc('measured_at')
            ->orderByDesc('id');
    }

    public static function generatePlainDeviceToken(): string
    {
        return 'leaf_'.Str::random(64);
    }

    public static function hashDeviceToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public static function findForDeviceToken(string $plainToken): ?self
    {
        if ($plainToken === '' || strlen($plainToken) > 255) {
            return null;
        }

        return self::query()
            ->where('device_token_hash', self::hashDeviceToken($plainToken))
            ->first();
    }

    public function issueDeviceToken(?DateTimeInterface $expiresAt = null): string
    {
        $plainToken = self::generatePlainDeviceToken();

        $this->forceFill([
            'device_token_hash' => self::hashDeviceToken($plainToken),
            'device_token_expires_at' => $expiresAt,
            'device_token_last_used_at' => null,
            'device_token_revoked_at' => null,
        ])->save();

        return $plainToken;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function hasRevokedDeviceToken(): bool
    {
        return $this->status === self::STATUS_REVOKED
            || $this->device_token_revoked_at !== null;
    }

    public function hasExpiredDeviceToken(): bool
    {
        return $this->device_token_expires_at !== null
            && $this->device_token_expires_at->isPast();
    }

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
