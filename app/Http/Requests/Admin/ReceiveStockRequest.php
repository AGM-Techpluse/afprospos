<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class ReceiveStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'integer'],
            'condition' => ['required', 'string', 'in:new,used_grade_a,used_grade_b,used_grade_c,refurbished'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'imeis' => ['nullable', 'array'],
            'imeis.*' => ['string', 'max:20'],
        ];
    }
}
