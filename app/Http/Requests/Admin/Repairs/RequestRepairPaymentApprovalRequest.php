<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RequestRepairPaymentApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'financial_status' => ['required', Rule::in(['partially_paid', 'fully_paid'])],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'method' => ['required', Rule::in(['cash', 'pos_terminal', 'bank_transfer'])],
        ];
    }
}
