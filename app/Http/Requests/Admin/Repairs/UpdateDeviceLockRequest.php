<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateDeviceLockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_lock_type' => ['required', Rule::in(['none', 'code', 'pattern'])],
            'device_lock_value' => ['nullable', 'required_unless:device_lock_type,none', 'string', 'max:100'],
        ];
    }
}
