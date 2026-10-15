<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs;

use Illuminate\Foundation\Http\FormRequest;

final class CompleteRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resolution_notes' => ['nullable', 'string'],
            'fixed_faults' => ['nullable', 'array'],
            'fixed_faults.*.diagnosis_case_fault_id' => ['nullable', 'integer', 'exists:diagnosis_case_faults,id'],
            'fixed_faults.*.device_problem_tag_id' => ['nullable', 'integer', 'exists:device_problem_tags,id'],
            'fixed_faults.*.common_component_id' => ['nullable', 'integer', 'exists:common_components,id'],
            'fixed_faults.*.label' => ['required', 'string', 'max:100'],
            'fixed_faults.*.notes' => ['nullable', 'string'],
        ];
    }
}
