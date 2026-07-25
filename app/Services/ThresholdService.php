<?php

namespace App\Services;

use App\Models\SystemSetting;

class ThresholdService
{
    private ?array $cachedThresholds = null;

    /**
     * Read a threshold float value from settings array or setting lookup with fallback.
     */
    public function getSettingFloat(string $key, ?float $default = null, ?array $settingsDict = null): ?float
    {
        $value = $settingsDict !== null
            ? ($settingsDict[$key] ?? null)
            : SystemSetting::getValue($key);

        if (is_numeric($value)) {
            return (float) $value;
        }

        return $default;
    }

    /**
     * Determine if both low and high thresholds are configured.
     */
    public function thresholdsAvailable(?float $low, ?float $high): bool
    {
        return $low !== null && $high !== null;
    }

    /**
     * Format a threshold range label (e.g., "18-25°C" or "Not available").
     */
    public function formatThresholdRange(?float $low, ?float $high, string $unit = ''): string
    {
        if (! $this->thresholdsAvailable($low, $high)) {
            return 'Not available';
        }

        return $this->formatThresholdValue($low).'-'.$this->formatThresholdValue($high).$unit;
    }

    /**
     * Format a single threshold number (trimming unnecessary zeroes).
     */
    public function formatThresholdValue(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /**
     * Scale a telemetry value into a 0-100 percentage for UI gauges.
     */
    public function scaleTelemetryPercentage(?float $value, ?float $low, ?float $high): float
    {
        if ($value === null || ! $this->thresholdsAvailable($low, $high) || $high <= $low) {
            return 0;
        }

        return min(100, max(0, (($value - $low) / ($high - $low)) * 100));
    }

    /**
     * Determine standard threshold status string (LOW, NORMAL, HIGH, etc.).
     */
    public function resolveStatus(?float $value, ?float $low, ?float $high, string $lowLabel = 'LOW', string $highLabel = 'HIGH'): string
    {
        if ($value === null || ! $this->thresholdsAvailable($low, $high)) {
            return 'Waiting';
        }

        if ($value < $low) {
            return $lowLabel;
        }

        if ($value > $high) {
            return $highLabel;
        }

        return 'NORMAL';
    }

    /**
     * Determine status type string for UI styling ('online', 'warning', 'standby').
     */
    public function resolveStatusType(?float $value, ?float $low, ?float $high): string
    {
        if ($value === null || ! $this->thresholdsAvailable($low, $high)) {
            return 'standby';
        }

        if ($value < $low || $value > $high) {
            return 'warning';
        }

        return 'online';
    }

    /**
     * Check if a status warrants triggering a threshold alert.
     */
    public function shouldPushThresholdAlert(string $status): bool
    {
        return ! in_array($status, ['NORMAL', 'Waiting'], true);
    }

    /**
     * Get all current thresholds and target range labels efficiently in a single cached lookup.
     */
    public function getAllThresholds(): array
    {
        if ($this->cachedThresholds !== null) {
            return $this->cachedThresholds;
        }

        $settings = SystemSetting::getAllSettings();

        $temperatureLow = $this->getSettingFloat('temperature_min', 18.0, $settings);
        $temperatureHigh = $this->getSettingFloat('temperature_max', 25.0, $settings);
        $humidityLow = $this->getSettingFloat('humidity_min', 60.0, $settings);
        $humidityHigh = $this->getSettingFloat('humidity_max', 80.0, $settings);
        $waterTemperatureLow = $this->getSettingFloat('water_temperature_min', 18.0, $settings);
        $waterTemperatureHigh = $this->getSettingFloat('water_temperature_max', 24.0, $settings);
        $phLow = $this->getSettingFloat('ph_min', 5.5, $settings);
        $phHigh = $this->getSettingFloat('ph_max', 6.5, $settings);
        $ecLow = $this->getSettingFloat('ec_min', 1.2, $settings);
        $ecHigh = $this->getSettingFloat('ec_max', 2.0, $settings);
        $waterFlowLow = $this->getSettingFloat('water_flow_min', 0.5, $settings);
        $waterFlowHigh = $this->getSettingFloat('water_flow_max', 2.0, $settings);
        $waterLevelLow = $this->getSettingFloat('water_level_min', 20.0, $settings);
        $waterLevelHigh = $this->getSettingFloat('water_level_max', 80.0, $settings);

        return $this->cachedThresholds = [
            'temperatureLow' => $temperatureLow,
            'temperatureHigh' => $temperatureHigh,
            'humidityLow' => $humidityLow,
            'humidityHigh' => $humidityHigh,
            'waterTemperatureLow' => $waterTemperatureLow,
            'waterTemperatureHigh' => $waterTemperatureHigh,
            'phLow' => $phLow,
            'phHigh' => $phHigh,
            'ecLow' => $ecLow,
            'ecHigh' => $ecHigh,
            'waterFlowLow' => $waterFlowLow,
            'waterFlowHigh' => $waterFlowHigh,
            'waterLevelLow' => $waterLevelLow,
            'waterLevelHigh' => $waterLevelHigh,

            'temperatureTargetLabel' => $this->formatThresholdRange($temperatureLow, $temperatureHigh, '°C'),
            'humidityTargetLabel' => $this->formatThresholdRange($humidityLow, $humidityHigh, '%'),
            'waterTemperatureTargetLabel' => $this->formatThresholdRange($waterTemperatureLow, $waterTemperatureHigh, '°C'),
            'phTargetLabel' => $this->formatThresholdRange($phLow, $phHigh),
            'ecTargetLabel' => $this->formatThresholdRange($ecLow, $ecHigh),
            'waterFlowTargetLabel' => $this->formatThresholdRange($waterFlowLow, $waterFlowHigh),
            'waterLevelTargetLabel' => $this->formatThresholdRange($waterLevelLow, $waterLevelHigh, '%'),
        ];
    }
}
