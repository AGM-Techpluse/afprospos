<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs;

use Illuminate\Foundation\Http\FormRequest;

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
            'down_payment_method' => ['nullable', 'string', 'in:cash,pos_terminal,bank_transfer'],
            'repair_cost_minor' => ['required', 'integer', 'min:1'],
            'diagnosis_fee_minor' => ['nullable', 'integer', 'min:1'],
            'special_agreement_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
