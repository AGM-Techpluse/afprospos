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
            'name' => ['required', 'string', 'max:150'],
            'shop_id' => ['nullable', 'integer', 'exists:shops,id'],
            'department' => ['required', 'string', Rule::in(array_keys(config('afprospos.expense_departments')))],
            'category' => ['required', 'string', Rule::in(array_keys(config('afprospos.expense_categories')))],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', 'string', Rule::in(array_keys(config('afprospos.expense_payment_methods')))],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'fund_type' => ['required', 'string', Rule::in(['disbursement', 'reimbursement'])],
            'reimbursed_to_staff_id' => [
                Rule::requiredIf(fn () => $this->input('fund_type') === 'reimbursement'),
                'nullable',
                'integer',
                'exists:staff,id',
            ],
        ];
    }
}
