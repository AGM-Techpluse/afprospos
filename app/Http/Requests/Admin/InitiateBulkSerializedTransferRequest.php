<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class InitiateBulkSerializedTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'units' => ['required', 'array', 'min:1'],
            'units.*.sku_id' => ['required', 'integer'],
            'units.*.inventory_item_id' => ['required', 'integer'],
            'from_shop_id' => ['required', 'integer'],
            'to_shop_id' => ['required', 'integer', 'different:from_shop_id'],
        ];
    }
}
