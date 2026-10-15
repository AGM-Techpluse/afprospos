<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateDiagnosisCaseRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['device_lock_type' => $this->input('device_lock_type', 'none')]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'device_model_id' => ['nullable', 'integer', 'exists:device_models,id'],
            'diagnosis_technician_staff_id' => ['nullable', 'integer', 'exists:staff,id'],
            'device_make' => ['required', 'string', 'max:100'],
            'device_model' => ['required', 'string', 'max:150'],
            'reported_issue' => ['nullable', 'string'],
            'device_imei_serial' => ['nullable', 'string', 'max:50'],
            'device_lock_type' => ['nullable', Rule::in(['none', 'code', 'pattern'])],
            'device_lock_value' => ['nullable', 'required_unless:device_lock_type,none', 'string', 'max:100'],
            'revealed_faults' => ['nullable', 'array'],
            'revealed_faults.*.device_problem_tag_id' => ['nullable', 'integer', 'exists:device_problem_tags,id'],
            'revealed_faults.*.label' => ['required', 'string', 'max:100'],
            'revealed_faults.*.notes' => ['nullable', 'string'],
        ];
    }
}
