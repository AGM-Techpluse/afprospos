<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateRepairJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** `required_unless:device_lock_type,none` only exempts device_lock_value when device_lock_type is literally present as "none" — an omitted field isn't the same as "none" to that rule, so default it before validation runs. */
    protected function prepareForValidation(): void
    {
        $this->merge(['device_lock_type' => $this->input('device_lock_type', 'none')]);
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'device_make' => ['required', 'string', 'max:100'],
            'device_model' => ['required', 'string', 'max:150'],
            'reported_issue' => ['nullable', 'string'],
            'device_imei_serial' => ['nullable', 'string', 'max:50'],
            'device_lock_type' => ['nullable', Rule::in(['none', 'code', 'pattern'])],
            'device_lock_value' => ['nullable', 'required_unless:device_lock_type,none', 'string', 'max:100'],
            'problem_tag_ids' => ['nullable', 'array'],
            'problem_tag_ids.*' => ['integer', 'exists:device_problem_tags,id'],
            'labour_charge_minor' => ['required', 'integer', 'min:0'],
        ];
    }
}
