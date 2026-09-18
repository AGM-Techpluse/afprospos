<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer\Warranty;

use Illuminate\Foundation\Http\FormRequest;

/** `customer_id` is never accepted from the client — the Controller forces it from the authenticated session (CPNC §0.2). */
final class CreateReturnRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sale_id' => ['required', 'integer'],
        ];
    }
}
