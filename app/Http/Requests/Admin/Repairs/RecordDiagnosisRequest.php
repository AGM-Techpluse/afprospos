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

    public function rules(): array
    {
        return [
            'component' => ['required', 'string', 'max:100'],
            'condition' => ['required', Rule::in(['working', 'faulty', 'not_tested', 'unable_to_test'])],
            'notes' => ['nullable', 'string'],
            'outcome' => ['nullable', Rule::in(['repairable', 'unrepairable', 'requires_further_assessment'])],
        ];
    }
}
