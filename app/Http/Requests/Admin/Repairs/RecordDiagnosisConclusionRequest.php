<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RecordDiagnosisConclusionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'root_cause' => ['required', 'string'],
            'outcome' => ['required', Rule::in(['repairable', 'unrepairable', 'requires_further_assessment'])],
        ];
    }
}
