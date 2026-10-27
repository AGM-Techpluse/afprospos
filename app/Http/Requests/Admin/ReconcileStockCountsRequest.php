<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReconcileStockCountsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route-level `permission:inventory.edit` middleware is the actual gate.
        return true;
    }

    public function rules(): array
    {
        return [
            'counts' => ['required', 'array', 'min:1'],
            'counts.*.sku_id' => ['required', 'integer', 'exists:inventory_skus,id'],
            'counts.*.shop_id' => ['required', 'integer', 'exists:shops,id'],
            'counts.*.physical_count' => ['required', 'integer', 'min:0'],
            'counts.*.reason_code' => ['required', 'string', Rule::in(array_keys(config('afprospos.stock_reconciliation_reasons')))],
            'counts.*.note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
