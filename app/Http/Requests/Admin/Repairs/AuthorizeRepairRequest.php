<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AuthorizeRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'down_payment_required_minor' => ['required', 'integer', 'min:0'],
            'down_payment_method' => ['required', Rule::in(['cash', 'pos_terminal', 'bank_transfer'])],
        ];
    }
}
