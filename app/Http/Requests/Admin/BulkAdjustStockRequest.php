<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** One shared reason for the whole batch (it describes the event — "Weekly stock count" — not each line) plus a per-row signed delta, positive to increase or negative to decrease (BRD INV-12, mirrors AdjustStockRequest's own rule). */
final class BulkAdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku_id' => ['required', 'integer'],
            'items.*.shop_id' => ['required', 'integer'],
            'items.*.delta' => ['required', 'integer', 'not_in:0'],
        ];
    }
}
