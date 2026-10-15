<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class CreateCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'walk_in_name' => ['nullable', 'string', 'max:150'],
            'walk_in_phone' => ['nullable', 'string', 'max:30'],
            'walk_in_email' => ['nullable', 'string', 'max:150'],
        ];
    }
}
