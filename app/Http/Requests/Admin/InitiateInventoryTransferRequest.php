<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class InitiateInventoryTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku_id' => ['required', 'integer'],
            'inventory_item_id' => ['nullable', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'from_shop_id' => ['required', 'integer'],
            'to_shop_id' => ['required', 'integer', 'different:from_shop_id'],
        ];
    }
}
