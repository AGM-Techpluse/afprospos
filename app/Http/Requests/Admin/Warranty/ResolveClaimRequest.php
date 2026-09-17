<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Warranty;

use Illuminate\Foundation\Http\FormRequest;

/** `remedy_reference_id` is an existing repair_jobs.id or skus.id depending on the claim's selected_remedy — validated against the right module's Lookup Contract in the Handler, not here. */
final class ResolveClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'remedy_reference_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
