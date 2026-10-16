<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs\DeviceCatalog;

use Illuminate\Foundation\Http\FormRequest;

final class SaveDeviceProblemTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_type_id' => ['nullable', 'integer', 'exists:device_types,id', $this->isMethod('post') ? 'required_without:device_model_id' : 'nullable'],
            'device_model_id' => ['nullable', 'integer', 'exists:device_models,id'],
            'label' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'commission_amount_minor' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
