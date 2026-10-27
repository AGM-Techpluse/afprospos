<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RecordExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route-level `permission:expenses.create` middleware is the actual gate.
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['nullable', 'integer', 'exists:shops,id'],
            'category' => ['required', 'string', Rule::in(array_keys(config('afprospos.expense_categories')))],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'allocation' => ['nullable', 'array'],
            'allocation.*' => ['integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
