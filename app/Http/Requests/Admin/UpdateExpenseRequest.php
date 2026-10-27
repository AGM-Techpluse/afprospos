<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route-level `permission:expenses.manage` middleware is the actual gate.
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::in(array_keys(config('afprospos.expense_categories')))],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'allocation' => ['nullable', 'array'],
            'allocation.*' => ['integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
