<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreInventoryMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'kind' => ['required', 'string', Rule::in(['category', 'unit', 'supplier'])],
            'name' => ['required', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:40', 'required_if:kind,supplier'],
            'contact_email' => ['nullable', 'email', 'max:190'],
        ];
    }
}
