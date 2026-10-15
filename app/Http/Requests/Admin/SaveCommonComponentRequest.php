<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveCommonComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100', Rule::unique('common_components', 'label')->ignore($this->route('component'))],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'commission_amount_minor' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
