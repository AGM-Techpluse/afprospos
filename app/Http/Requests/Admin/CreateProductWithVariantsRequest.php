<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'category' => ['required', 'string', 'max:255', Rule::exists('inventory_master_entries', 'name')->where('kind', 'category')],
            'unit' => ['nullable', 'string', 'max:120', Rule::exists('inventory_master_entries', 'name')->where('kind', 'unit')],
            'purpose' => ['nullable', 'string', 'in:sale,repair_part,both'],
            'supplier_id' => ['nullable', 'integer', Rule::exists('inventory_master_entries', 'id')->where('kind', 'supplier')],
            'product_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'condition' => ['required', 'string', 'in:new,used_grade_a,used_grade_b,used_grade_c,refurbished'],
            'is_serialized' => ['nullable', 'boolean'],
            'shop_id' => ['required', 'integer'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.cost_price_minor' => ['required', 'integer', 'min:0'],
            'variants.*.markup_percent' => ['nullable', 'numeric', 'min:0'],
            'variants.*.selling_price_minor_override' => ['nullable', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'variants.*.initial_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.initial_imeis' => ['nullable', 'array'],
            'variants.*.initial_imeis.*' => ['string', 'max:20'],
            'variants.*.initial_serial_numbers' => ['nullable', 'array'],
            'variants.*.initial_serial_numbers.*' => ['nullable', 'string', 'max:80'],
            'variants.*.attributes' => ['nullable', 'array'],
            'variants.*.attributes.brand' => ['nullable', 'string', 'max:100'],
            'variants.*.attributes.color' => ['nullable', 'string', 'max:100'],
            'variants.*.attributes.storage' => ['nullable', 'string', 'max:100'],
            'variants.*.attributes.material' => ['nullable', 'string', 'max:100'],
            'variants.*.attributes.compatibility' => ['nullable', 'string', 'max:255'],
            'variants.*.attributes.custom' => ['nullable', 'array'],
            'variants.*.attributes.custom.*' => ['nullable', 'string', 'max:255'],
            'variants.*.is_commissionable' => ['nullable', 'boolean'],
            'variants.*.commission_amount_minor' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
