<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ApplyDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['promotion', 'referral_voucher', 'trade_in_credit', 'store_credit'])],
            'source_id' => ['required', 'integer', 'min:1'],
            'amount_minor' => ['required', 'integer', 'min:1'],
        ];
    }
}
