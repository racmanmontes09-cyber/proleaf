<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    public static function booted(): void
    {
        static::saving(function (SystemSetting $setting): void {
            $setting->key = trim($setting->key);
        });

        static::saved(function (): void {
            static::flushCache();
        });

        static::deleted(function (): void {
            static::flushCache();
        });
    }

    /**
     * Get all system settings as a cached key-value dictionary.
     */
    public static function getAllSettings(): array
    {
        return Cache::remember('system_settings_dict', 3600, function (): array {
            return static::query()->pluck('value', 'key')->all();
        });
    }

    /**
     * Get a single setting value from cached dictionary.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $settings = static::getAllSettings();

        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    /**
     * Store or update a setting value and flush cache.
     */
    public static function putValue(string $key, mixed $value, array $attributes = []): self
    {
        $setting = static::query()->where('key', $key)->firstOrNew();
        $setting->fill(array_merge([
            'key' => $key,
            'value' => $value,
            'type' => $attributes['type'] ?? 'string',
            'group' => $attributes['group'] ?? 'general',
            'label' => $attributes['label'] ?? $key,
            'description' => $attributes['description'] ?? null,
        ], $attributes));
        $setting->save();

        return $setting;
    }

    /**
     * Batch store setting values with one upsert and one cache flush.
     *
     * @param  array<string, mixed>  $values
     * @param  array<string, array<string, mixed>>  $definitions
     */
    public static function putMany(array $values, array $definitions = []): void
    {
        $now = now();
        $rows = [];

        foreach ($values as $key => $value) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }

            $attributes = $definitions[$key] ?? [];
            $rows[] = [
                'key' => $key,
                'value' => $value,
                'type' => $attributes['type'] ?? 'string',
                'group' => $attributes['group'] ?? 'general',
                'label' => $attributes['label'] ?? $key,
                'description' => $attributes['description'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows === []) {
            return;
        }

        static::query()->upsert($rows, ['key'], [
            'value',
            'type',
            'group',
            'label',
            'description',
            'updated_at',
        ]);

        static::flushCache();
    }

    /**
     * Flush system settings cache.
     */
    public static function flushCache(): void
    {
        Cache::forget('system_settings_dict');
        Cache::forget('device.heartbeat_interval_seconds');
    }
}
