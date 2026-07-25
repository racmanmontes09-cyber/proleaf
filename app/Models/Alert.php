<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Support\Carbon;

class Alert extends Model
{
    protected $fillable = [
        'device_id',
        'telemetry_id',
        'title',
        'message',
        'sensor',
        'severity',
        'status',
        'value',
        'threshold',
        'metadata',
        'acknowledged_at',
        'acknowledged_by',
        'resolved_at',
    ];

    protected $casts = [
        'metadata' => AsArrayObject::class,
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function telemetry(): BelongsTo
    {
        return $this->belongsTo(Telemetry::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeWarning($query)
    {
        return $query->where('severity', 'warning');
    }

    public function scopeInfo($query)
    {
        return $query->where('severity', 'info');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'acknowledged']);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    public function scopeBetween($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function scopeRecent($query, int $limit = 20)
    {
        return $query->latest()->limit($limit);
    }

    public function scopeForDevice($query, Device $device)
    {
        return $query->where('device_id', $device->id);
    }

    public function toDashboardArray(): array
    {
        return [
            'key' => (string) $this->id,
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'severity' => $this->severity,
            'card_severity' => $this->severity === 'critical' ? 'danger' : $this->severity,
            'timestamp' => $this->created_at?->toIso8601String(),
            'time' => $this->created_at?->diffForHumans() ?? '',
            'icon' => $this->metadata['icon'] ?? 'alert-circle',
            'sensor' => $this->sensor,
            'acknowledged' => $this->status === 'acknowledged',
            'status' => $this->status,
            'metadata' => $this->metadata ?? [],
        ];
    }
}
