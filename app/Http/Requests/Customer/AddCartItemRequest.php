<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

final class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku_id' => ['required', 'integer', 'exists:inventory_skus,id'],
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
