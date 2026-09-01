<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'dashboard',
    ];

    protected $casts = [
        'dashboard' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function defaults(): array
    {
        return [
            'kpi_cards' => [
                'air_temperature' => true,
                'humidity' => false,
                'water_temperature' => true,
                'ph' => true,
                'ec' => true,
                'water_level' => true,
                'water_flow' => true,
            ],
            'show_chart' => true,
            'show_controller_card' => true,
            'show_actuator_relays' => true,
        ];
    }

    public static function forUser(?int $userId): array
    {
        if ($userId === null) {
            return static::defaults();
        }

        $pref = static::where('user_id', $userId)->first();

        if (! $pref || ! $pref->dashboard) {
            return static::defaults();
        }

        $dashboard = $pref->dashboard;
        unset($dashboard['appearance']);

        $merged = array_replace_recursive(static::defaults(), $dashboard);
        $merged['kpi_cards']['humidity'] = false;

        return $merged;
    }
}
