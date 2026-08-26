<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Telemetry extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'telemetries';

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'device_id',
        'air_temperature',
        'humidity',
        'water_temperature',
        'ph',
        'ec',
        'water_flow',
        'water_level',
        'measured_at',
        'received_at',
        'sequence_number',
        'firmware_version',
        'signal_strength',
        'battery_voltage',
        'payload_version',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'air_temperature' => 'float',
        'humidity' => 'float',
        'water_temperature' => 'float',
        'ph' => 'float',
        'ec' => 'float',
        'water_flow' => 'float',
        'water_level' => 'float',
        'measured_at' => 'datetime',
        'received_at' => 'datetime',
        'sequence_number' => 'integer',
        'signal_strength' => 'integer',
        'battery_voltage' => 'float',
        'payload_version' => 'integer',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Telemetry $telemetry): void {
            static $lastMicro = 0;
            $now = now();

            $telemetry->received_at ??= $now;
            $telemetry->payload_version ??= 1;

            if ($telemetry->measured_at === null) {
                $currentMicro = (int) ($now->timestamp * 1000000 + $now->micro);
                if ($currentMicro <= $lastMicro) {
                    $currentMicro = $lastMicro + 1;
                }
                $lastMicro = $currentMicro;

                $seconds = (int) ($currentMicro / 1000000);
                $micros = $currentMicro % 1000000;
                $telemetry->measured_at = Carbon::createFromTimestampUTC($seconds)->addMicroseconds($micros);
            }
        });
    }

    /**
     * Get the device associated with this telemetry.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Scope query to filter by device instance or device primary key / device_id string.
     */
    public function scopeForDevice(Builder $query, Device|int|string $device): Builder
    {
        if ($device instanceof Device) {
            return $query->where('device_id', $device->id);
        }

        if (is_numeric($device)) {
            return $query->where('device_id', (int) $device);
        }

        return $query->whereHas('device', function (Builder $q) use ($device) {
            $q->where('device_id', $device);
        });
    }

    /**
     * Scope query to order telemetries by latest measured_at timestamp.
     */
    public function scopeLatestReading(Builder $query): Builder
    {
        return $query->orderByDesc('measured_at')->orderByDesc('id');
    }

    /**
     * Scope query to order telemetries by latest measurement (alias).
     */
    public function scopeLatestMeasurement(Builder $query): Builder
    {
        return $this->scopeLatestReading($query);
    }

    /**
     * Scope query for latest telemetry of a specific device.
     */
    public function scopeLatestForDevice(Builder $query, Device|int|string $device): Builder
    {
        return $this->scopeForDevice($query, $device)->latestReading();
    }

    /**
     * Scope query to get the N most recent telemetries.
     */
    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->latestReading()->limit($limit);
    }

    /**
     * Scope query for telemetries measured today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->where('measured_at', '>=', now()->startOfDay());
    }

    /**
     * Scope query for telemetries measured within a specific timestamp range.
     */
    public function scopeBetween(Builder $query, DateTimeInterface|string $start, DateTimeInterface|string $end): Builder
    {
        return $query->whereBetween('measured_at', [$start, $end]);
    }

    /**
     * Scope query for telemetries measured within a specific timestamp range (alias).
     */
    public function scopeBetweenDates(Builder $query, DateTimeInterface|string $start, DateTimeInterface|string $end): Builder
    {
        return $this->scopeBetween($query, $start, $end);
    }

    /**
     * Scope query for telemetries measured in the last 24 hours.
     */
    public function scopeLast24Hours(Builder $query): Builder
    {
        return $query->where('measured_at', '>=', now()->subHours(24));
    }

    /**
     * Scope query for telemetries measured in the last 7 days.
     */
    public function scopeLast7Days(Builder $query): Builder
    {
        return $query->where('measured_at', '>=', now()->subDays(7));
    }

    /**
     * Scope query for telemetries measured in the last 30 days.
     */
    public function scopeLast30Days(Builder $query): Builder
    {
        return $query->where('measured_at', '>=', now()->subDays(30));
    }

    /**
     * Scope query to get the latest telemetry record for each device.
     */
    public function scopeLatestPerDevice(Builder $query): Builder
    {
        return $query->whereIn('id', function ($subQuery) {
            $subQuery->selectRaw('MAX(id)')
                ->from('telemetries')
                ->groupBy('device_id');
        });
    }

    /**
     * Helper accessor for checking if battery is low (< 3.3V).
     */
    public function getIsBatteryLowAttribute(): bool
    {
        return $this->battery_voltage !== null && $this->battery_voltage < 3.3;
    }

    /**
     * Helper accessor for signal quality evaluation.
     */
    public function getSignalQualityAttribute(): string
    {
        if ($this->signal_strength === null) {
            return 'Unknown';
        }

        if ($this->signal_strength >= -60) {
            return 'Excellent';
        }

        if ($this->signal_strength >= -75) {
            return 'Good';
        }

        if ($this->signal_strength >= -90) {
            return 'Fair';
        }

        return 'Weak';
    }
}
