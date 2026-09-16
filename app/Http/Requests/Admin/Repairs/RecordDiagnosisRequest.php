<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RecordDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `component` is required unless `outcome` is present — a technician who
     * already recorded every observation can finalize on outcome alone,
     * without a throwaway component row. `condition` only matters when a
     * component is actually being recorded.
     */
    public function rules(): array
    {
        return [
            'component' => ['nullable', 'required_without:outcome', 'string', 'max:100'],
            'condition' => ['nullable', 'required_with:component', Rule::in(['working', 'faulty', 'not_tested', 'unable_to_test'])],
            'notes' => ['nullable', 'string'],
            'outcome' => ['nullable', Rule::in(['repairable', 'unrepairable', 'requires_further_assessment'])],
        ];
    }
}
