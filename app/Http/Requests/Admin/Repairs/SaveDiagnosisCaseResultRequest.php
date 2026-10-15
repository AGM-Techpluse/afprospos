<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveDiagnosisCaseResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'outcome' => ['required', Rule::in(['repairable', 'unrepairable', 'requires_further_assessment'])],
            'diagnosis_notes' => ['nullable', 'string'],
            'diagnosis_fee_minor' => ['nullable', 'integer', 'min:0'],
            'revealed_faults' => ['nullable', 'array'],
            'revealed_faults.*.device_problem_tag_id' => ['nullable', 'integer', 'exists:device_problem_tags,id'],
            'revealed_faults.*.label' => ['required', 'string', 'max:100'],
            'revealed_faults.*.notes' => ['nullable', 'string'],
        ];
    }
}
