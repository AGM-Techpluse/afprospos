<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs;

use Illuminate\Foundation\Http\FormRequest;

final class CreateRepairFromDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'fault_ids' => ['nullable', 'array'],
            'fault_ids.*' => ['integer', 'exists:diagnosis_case_faults,id'],
            'reopened_from_repair_job_id' => ['nullable', 'integer', 'exists:repair_jobs,id'],
        ];
    }
}
