<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProductMetadataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255', Rule::exists('inventory_master_entries', 'name')->where('kind', 'category')],
            'unit' => ['required', 'string', 'max:120', Rule::exists('inventory_master_entries', 'name')->where('kind', 'unit')],
        ];
    }
}
