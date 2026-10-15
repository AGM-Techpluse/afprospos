<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs;

use Illuminate\Foundation\Http\FormRequest;

final class RecordQualityCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'outcome' => ['required', 'string', 'in:passed,failed,not_testable'],
            // A failed check or an untestable device must say why.
            'notes' => ['nullable', 'string', 'max:2000', 'required_if:outcome,failed,not_testable'],
        ];
    }
}
