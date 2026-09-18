<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Warranty;

use Illuminate\Foundation\Http\FormRequest;

final class AssessTradeInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assessed_value_minor' => ['required', 'integer', 'min:1'],
        ];
    }
}
