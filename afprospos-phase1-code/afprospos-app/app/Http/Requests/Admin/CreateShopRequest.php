<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class CreateShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku_prefix_code' => ['required', 'string', 'max:10', 'alpha_num'],
            'address' => ['required', 'string'],
            'contact_phone' => ['required', 'string', 'max:32'],
            'contact_email' => ['required', 'email'],
        ];
    }
}
