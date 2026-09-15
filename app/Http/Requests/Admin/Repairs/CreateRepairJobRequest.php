<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs;

use Illuminate\Foundation\Http\FormRequest;

final class CreateRepairJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'device_make' => ['required', 'string', 'max:100'],
            'device_model' => ['required', 'string', 'max:150'],
            'labour_charge_minor' => ['required', 'integer', 'min:0'],
        ];
    }
}
