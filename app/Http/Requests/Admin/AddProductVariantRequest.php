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
            'is_serialized' => ['nullable', 'boolean'],
            'cost_price_minor' => ['required', 'integer', 'min:0'],
            'markup_percent' => ['nullable', 'numeric', 'min:0'],
            'selling_price_minor_override' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'initial_quantity' => ['nullable', 'integer', 'min:0'],
            'initial_imeis' => ['nullable', 'array'],
            'initial_imeis.*' => ['string', 'max:20'],
            'initial_serial_numbers' => ['nullable', 'array'],
            'initial_serial_numbers.*' => ['nullable', 'string', 'max:80'],
            'attributes' => ['nullable', 'array'],
            'attributes.brand' => ['nullable', 'string', 'max:100'],
            'attributes.color' => ['nullable', 'string', 'max:100'],
            'attributes.storage' => ['nullable', 'string', 'max:100'],
            'attributes.material' => ['nullable', 'string', 'max:100'],
            'attributes.compatibility' => ['nullable', 'string', 'max:255'],
            'attributes.custom' => ['nullable', 'array'],
            'attributes.custom.*' => ['nullable', 'string', 'max:255'],
            'is_commissionable' => ['nullable', 'boolean'],
            'commission_amount_minor' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
