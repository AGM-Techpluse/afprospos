<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs\DeviceCatalog;

use Illuminate\Foundation\Http\FormRequest;

final class SaveDeviceTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'icon' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
