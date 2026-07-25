<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TelemetryStoreRequest extends FormRequest
{
    private const TELEMETRY_FIELDS = [
        'air_temperature',
        'humidity',
        'water_temperature',
        'ph',
        'ec',
        'water_flow',
        'water_level',
        'battery_voltage',
        'signal_strength',
    ];

    /**
     * Determine if the user is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            'device_id' => ['sometimes', 'string', 'max:100'],
            'air_temperature' => ['nullable', 'numeric', 'between:-50,100'],
            'humidity' => ['nullable', 'numeric', 'between:0,100'],
            'water_temperature' => ['nullable', 'numeric', 'between:-10,80'],
            'ph' => ['nullable', 'numeric', 'between:0,14'],
            'ec' => ['nullable', 'numeric', 'between:0,20'],
            'water_flow' => ['nullable', 'numeric', 'between:0,500'],
            'water_level' => ['nullable', 'numeric', 'between:0,100'],
            'measured_at' => ['nullable', 'date'],
            'sequence_number' => ['nullable', 'integer', 'min:0'],
            'firmware_version' => ['nullable', 'string', 'max:50'],
            'signal_strength' => ['nullable', 'integer', 'between:-150,0'],
            'battery_voltage' => ['nullable', 'numeric', 'between:0,30'],
            'payload_version' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (self::TELEMETRY_FIELDS as $field) {
                    if ($this->filled($field)) {
                        return;
                    }
                }

                $validator->errors()->add('telemetry', 'At least one telemetry value is required.');
            },
        ];
    }

    public function telemetryPayload(): array
    {
        $payload = $this->safe()->only([
            'air_temperature',
            'humidity',
            'water_temperature',
            'ph',
            'ec',
            'water_flow',
            'water_level',
            'measured_at',
            'sequence_number',
            'firmware_version',
            'signal_strength',
            'battery_voltage',
            'payload_version',
        ]);

        $payload['received_at'] = now();

        return array_filter($payload, fn ($value) => $value !== null);
    }
}
