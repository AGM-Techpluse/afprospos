<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSkuCommissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_commissionable' => ['required', 'boolean'],
            'commission_amount_minor' => ['nullable', 'integer', 'min:0', Rule::requiredIf(fn () => $this->boolean('is_commissionable'))],
        ];
    }
}
