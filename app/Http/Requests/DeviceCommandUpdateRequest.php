<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeviceCommandUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:completed,failed'],
            'result' => ['nullable', 'array'],
            'failure_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
