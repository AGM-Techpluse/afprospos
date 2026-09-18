<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Warranty;

use Illuminate\Foundation\Http\FormRequest;

final class ApplyTradeInCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'checkout_id' => ['required', 'integer'],
        ];
    }
}
