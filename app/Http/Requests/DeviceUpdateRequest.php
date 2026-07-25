<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeviceUpdateRequest extends FormRequest
{
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
            'name'             => ['nullable', 'string', 'max:255'],
            'firmware_version' => ['nullable', 'string', 'max:50'],
            'local_ip_address' => ['nullable', 'ip'],
            'wifi_rssi'        => ['nullable', 'integer', 'between:-120,0'],
            'uptime_seconds'   => ['nullable', 'integer', 'min:0'],
            'free_heap'        => ['nullable', 'integer', 'min:0'],
            'last_boot_at'     => ['nullable', 'date'],
        ];
    }
}
