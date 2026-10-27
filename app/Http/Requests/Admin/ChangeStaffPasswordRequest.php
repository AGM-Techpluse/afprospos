<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class ChangeStaffPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Every authenticated staff member may change their own password — no object-level check needed, there is no "whose" here beyond the logged-in actor.
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
