<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** One destination shop for the whole batch, one quantity per selected SKU — non-serialized only (the Stock index this is triggered from never lists serialized units, DBDD §14.5). */
final class BulkInitiateInventoryTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_shop_id' => ['required', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku_id' => ['required', 'integer'],
            'items.*.from_shop_id' => ['required', 'integer', 'different:to_shop_id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
