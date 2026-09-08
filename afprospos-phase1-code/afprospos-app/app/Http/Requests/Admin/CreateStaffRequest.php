<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class CreateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route-level `permission:staff.create` middleware is the actual
        // gate; returning true here since this is not an object-level
        // ("can I edit THIS specific staff member") authorization check.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'initial_role_names' => ['sometimes', 'array'],
            'initial_role_names.*' => ['string'],
            'initial_shop_ids' => ['sometimes', 'array'],
            'initial_shop_ids.*' => ['integer'],
        ];
    }
}
