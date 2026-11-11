<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSkuPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cost_price_minor' => ['required', 'integer', 'min:0'],
            'markup_percent' => ['required', 'numeric', 'min:0'],
            'selling_price_minor_override' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
