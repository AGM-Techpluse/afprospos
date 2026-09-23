<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** INV-BR-17: variants are always non-serialized (INV-BR-16) — there is no per-variant is_serialized field, unlike CreateProductRequest's single-item path. */
final class CreateProductWithVariantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'condition' => ['required', 'string', 'in:new,used_grade_a,used_grade_b,used_grade_c,refurbished'],
            'shop_id' => ['required', 'integer'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.cost_price_minor' => ['required', 'integer', 'min:0'],
            'variants.*.markup_percent' => ['required', 'numeric', 'min:0'],
            'variants.*.selling_price_minor_override' => ['nullable', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'variants.*.initial_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.attributes' => ['nullable', 'array'],
            'variants.*.attributes.brand' => ['nullable', 'string', 'max:100'],
            'variants.*.attributes.color' => ['nullable', 'string', 'max:100'],
            'variants.*.attributes.material' => ['nullable', 'string', 'max:100'],
            'variants.*.attributes.compatibility' => ['nullable', 'string', 'max:255'],
            'variants.*.attributes.custom' => ['nullable', 'array'],
            'variants.*.attributes.custom.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
