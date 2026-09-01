<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\DeviceCommand;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\SystemSetting;

class Device extends Model
{
    use HasFactory;

    private static array $tokenLookupCache = [];

    public const TYPE_SENSOR = 'sensor';

    public const TYPE_CAMERA = 'camera';

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
        'actuator_cooling_fan',
        'actuator_nutrient_pump_a',
        'actuator_nutrient_pump_b',
        'actuator_ph_up_pump',
        'actuator_ph_down_pump',
        'actuator_states_updated_at',
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
        'actuator_cooling_fan' => 'boolean',
        'actuator_nutrient_pump_a' => 'boolean',
        'actuator_nutrient_pump_b' => 'boolean',
        'actuator_ph_up_pump' => 'boolean',
        'actuator_ph_down_pump' => 'boolean',
        'actuator_states_updated_at' => 'datetime',
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

    public function greenhouse(): BelongsTo
    {
        return $this->belongsTo(Greenhouse::class);
    }

    public function deviceCommands(): HasMany
    {
        return $this->hasMany(DeviceCommand::class);
    }

    public function latestTelemetry(): HasOne
    {
        return $this->hasOne(Telemetry::class)->ofMany('measured_at', 'max');
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

        $tokenHash = self::hashDeviceToken($plainToken);

        if (isset(self::$tokenLookupCache[$tokenHash])) {
            return self::$tokenLookupCache[$tokenHash];
        }

        $device = self::query()
            ->where('device_token_hash', $tokenHash)
            ->first();

        if ($device === null) {
            return null;
        }

        self::$tokenLookupCache[$tokenHash] = $device;

        return $device;
    }

    public function issueDeviceToken(?DateTimeInterface $expiresAt = null): string
    {
        $plainToken = self::generatePlainDeviceToken();
        $tokenHash = self::hashDeviceToken($plainToken);

        $this->forceFill([
            'device_token_hash' => $tokenHash,
            'device_token_expires_at' => $expiresAt,
            'device_token_last_used_at' => null,
            'device_token_revoked_at' => null,
        ])->save();

        unset(self::$tokenLookupCache[$tokenHash]);

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
     *
     * The grace period adapts to the configured heartbeat interval so a
     * healthy device never appears offline between normal heartbeats.
     */
    public function getIsOnlineAttribute(): bool
    {
        if (! $this->last_seen_at) {
            return false;
        }

        return $this->last_seen_at->diffInSeconds(now()) <= self::effectiveOnlineGraceSeconds();
    }

    /**
     * Compute the online grace period: at least the configured minimum,
     * but always larger than the heartbeat interval plus a safety margin
     * for MQTT/WiFi jitter and scheduler delays.
     *
     * The margin is 2× the heartbeat interval so a device must miss at
     * least two consecutive heartbeats before being marked offline.
     */
    public static function effectiveOnlineGraceSeconds(): int
    {
        $configuredGrace = (int) config('leaf.device_status.online_grace_seconds', 60);
        $heartbeatInterval = self::getHeartbeatIntervalSeconds();

        // Grace must exceed 2× heartbeat interval plus jitter margin.
        $requiredGrace = ($heartbeatInterval * 2) + 10;

        return max($configuredGrace, $requiredGrace);
    }

    /**
     * Get the device heartbeat interval in seconds from SystemSetting,
     * cached for 60 seconds to avoid repeated DB queries.
     */
    public static function getHeartbeatIntervalSeconds(): int
    {
        return (int) Cache::remember('device.heartbeat_interval_seconds', 60, function () {
            $value = SystemSetting::getValue('heartbeat_interval');

            // Keep in sync with the runtime configuration default (30s).
            return $value !== null ? max(1, (int) $value) : 30;
        });
    }
}
