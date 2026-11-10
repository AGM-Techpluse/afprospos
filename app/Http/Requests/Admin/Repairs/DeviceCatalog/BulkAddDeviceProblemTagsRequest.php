<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs\DeviceCatalog;

use Illuminate\Foundation\Http\FormRequest;

final class BulkAddDeviceProblemTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_model_id' => ['required', 'integer', 'exists:device_models,id'],
            'labels' => ['required', 'array', 'min:1'],
            'labels.*' => ['required', 'string', 'max:100'],
            'starting_sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
