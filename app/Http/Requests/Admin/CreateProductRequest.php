<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class CreateProductRequest extends FormRequest
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
            'is_serialized' => ['required', 'boolean'],
            'cost_price_minor' => ['required', 'integer', 'min:0'],
            'markup_percent' => ['required', 'numeric', 'min:0'],
            'selling_price_minor_override' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'shop_id' => ['required', 'integer'],
            'initial_quantity' => ['nullable', 'integer', 'min:0'],
            'initial_imeis' => ['nullable', 'array'],
            'initial_imeis.*' => ['string', 'max:20'],
        ];
    }
}
