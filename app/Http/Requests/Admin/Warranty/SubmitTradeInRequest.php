<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Warranty;

use Illuminate\Foundation\Http\FormRequest;

final class SubmitTradeInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'related_checkout_id' => ['nullable', 'integer'],
            'device_make' => ['required', 'string', 'max:100'],
            'device_model' => ['required', 'string', 'max:100'],
            'device_imei' => ['nullable', 'string', 'max:50'],
            'device_condition' => ['required', 'string', 'max:500'],
        ];
    }
}
