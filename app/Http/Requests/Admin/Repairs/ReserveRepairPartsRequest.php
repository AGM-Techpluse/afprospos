<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs;

use Illuminate\Foundation\Http\FormRequest;

final class ReserveRepairPartsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku_id' => ['required', 'integer', 'exists:inventory_skus,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
