<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** BRD PAY-01/PAY-05: payment method + reference (terminal/transfer ref) for reconciliation. */
final class CompleteSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['cash', 'pos_terminal', 'bank_transfer', 'in_app'])],
            'payment_reference' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $this->input('payment_method') !== 'cash')],
        ];
    }
}
