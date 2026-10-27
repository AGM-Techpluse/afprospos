<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSkuAttributesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attributes' => ['nullable', 'array'],
            'attributes.brand' => ['nullable', 'string', 'max:100'],
            'attributes.color' => ['nullable', 'string', 'max:100'],
            'attributes.storage' => ['nullable', 'string', 'max:100'],
            'attributes.material' => ['nullable', 'string', 'max:100'],
            'attributes.compatibility' => ['nullable', 'string', 'max:255'],
            'attributes.custom' => ['nullable', 'array'],
            'attributes.custom.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
