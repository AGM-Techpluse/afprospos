<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Warranty;

use Illuminate\Foundation\Http\FormRequest;

final class ProcessReturnRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_transaction_id' => ['required', 'integer', 'exists:payments_transactions,id'],
        ];
    }
}
