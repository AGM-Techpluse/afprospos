<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class AddProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'integer'],
            'cost_price_minor' => ['required', 'integer', 'min:0'],
            'markup_percent' => ['required', 'numeric', 'min:0'],
            'selling_price_minor_override' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'initial_quantity' => ['nullable', 'integer', 'min:0'],
            'attributes' => ['nullable', 'array'],
            'attributes.brand' => ['nullable', 'string', 'max:100'],
            'attributes.color' => ['nullable', 'string', 'max:100'],
            'attributes.material' => ['nullable', 'string', 'max:100'],
            'attributes.compatibility' => ['nullable', 'string', 'max:255'],
            'attributes.custom' => ['nullable', 'array'],
            'attributes.custom.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
