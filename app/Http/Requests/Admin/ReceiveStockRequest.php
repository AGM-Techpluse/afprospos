<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'condition' => ['required', 'string', 'max:120', Rule::exists('inventory_master_entries', 'name')->where('kind', 'condition')],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'imeis' => ['nullable', 'array'],
            'imeis.*' => ['string', 'max:20'],
            'serial_numbers' => ['nullable', 'array'],
            'serial_numbers.*' => ['nullable', 'string', 'max:80'],
        ];
    }
}
