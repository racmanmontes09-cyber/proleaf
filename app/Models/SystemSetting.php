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

        static::flushCache();

        return $setting;
    }

    /**
     * Flush system settings cache.
     */
    public static function flushCache(): void
    {
        Cache::forget('system_settings_dict');
    }
}
