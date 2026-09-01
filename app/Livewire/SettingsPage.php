<?php

namespace App\Livewire;

use App\Models\SystemSetting;
use App\Services\DeviceRuntimeConfigurationService;
use Livewire\Component;

class SettingsPage extends Component
{
    public array $settings = [];

    public bool $saved = false;

    public string $message = '';

    public function mount(): void
    {
        if (! auth()->user()?->canPerform('settings.view')) {
            abort(403);
        }

        $this->loadSettings();
    }

    public function save(): void
    {
        $this->reset('saved', 'message');

        $this->validate();

        if (! auth()->user()?->canPerform('settings.update')) {
            $this->message = 'Forbidden.';
            return;
        }

        $this->saveSettings();
        $publishSummary = $this->publishRuntimeConfiguration();

        $this->saved = true;
        $this->message = 'System parameters saved successfully.';
        $this->appendPublishWarning($publishSummary);
    }

    public function resetToDefaults(): void
    {
        $this->reset('saved', 'message');

        if (! auth()->user()?->canPerform('settings.update')) {
            $this->message = 'Forbidden.';
            return;
        }

        SystemSetting::putMany(
            $this->defaultSettingValues(),
            DeviceRuntimeConfigurationService::APPROVED_SETTING_DEFINITIONS
        );

        $this->loadSettings();
        $publishSummary = $this->publishRuntimeConfiguration();
        $this->saved = true;
        $this->message = 'System parameters reset to defaults.';
        $this->appendPublishWarning($publishSummary);
    }

    protected function rules(): array
    {
        return [
            'settings.temperature_min' => ['required', 'numeric', 'lt:settings.temperature_max'],
            'settings.temperature_max' => ['required', 'numeric', 'gt:settings.temperature_min'],
            'settings.ph_min' => ['required', 'numeric', 'lt:settings.ph_max'],
            'settings.ph_max' => ['required', 'numeric', 'gt:settings.ph_min'],
            'settings.ec_min' => ['required', 'numeric', 'lt:settings.ec_max'],
            'settings.ec_max' => ['required', 'numeric', 'gt:settings.ec_min'],
            'settings.sensor_upload_interval' => ['required', 'integer', 'min:1'],
            'settings.heartbeat_interval' => ['required', 'integer', 'min:1'],
        ];
    }

    public function render()
    {
        return view('livewire.settings-page');
    }

    public function loadSettings(): void
    {
        $rows = SystemSetting::query()
            ->whereIn('key', array_keys(DeviceRuntimeConfigurationService::APPROVED_SETTING_DEFINITIONS))
            ->pluck('value', 'key')
            ->all();

        $this->settings = [];

        foreach (DeviceRuntimeConfigurationService::APPROVED_SETTING_DEFINITIONS as $key => $attributes) {
            $this->settings[$key] = $rows[$key] ?? $attributes['value'];
        }
    }

    protected function saveSettings(): void
    {
        SystemSetting::putMany(
            $this->currentSettingValues(),
            DeviceRuntimeConfigurationService::APPROVED_SETTING_DEFINITIONS
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function currentSettingValues(): array
    {
        $values = [];

        foreach (DeviceRuntimeConfigurationService::APPROVED_SETTING_DEFINITIONS as $key => $attributes) {
            $values[$key] = $this->settings[$key] ?? $attributes['value'];
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultSettingValues(): array
    {
        $values = [];

        foreach (DeviceRuntimeConfigurationService::APPROVED_SETTING_DEFINITIONS as $key => $attributes) {
            $values[$key] = $attributes['value'];
        }

        return $values;
    }

    /**
     * @return array{attempted:int,published:int,failed:int}
     */
    protected function publishRuntimeConfiguration(): array
    {
        try {
            return app(DeviceRuntimeConfigurationService::class)->publishCurrentConfigurationToAllDevices();
        } catch (\Throwable) {
            return [
                'attempted' => 0,
                'published' => 0,
                'failed' => 1,
            ];
        }
    }

    /**
     * @param  array{attempted:int,published:int,failed:int}  $summary
     */
    protected function appendPublishWarning(array $summary): void
    {
        if ($summary['failed'] > 0) {
            $this->message .= ' MQTT runtime configuration publish failed for '.$summary['failed'].' device(s).';
        }
    }
}
