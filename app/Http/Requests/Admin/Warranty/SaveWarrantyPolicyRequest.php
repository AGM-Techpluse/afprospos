<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Warranty;

use Illuminate\Foundation\Http\FormRequest;

final class SaveWarrantyPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'coverage_duration_days' => ['required', 'integer', 'min:1'],
            'coverage_start_point' => ['required', 'string', 'in:sale_date,collection_date'],
            'covered_scope' => ['required', 'array', 'min:1'],
            'covered_scope.*' => ['string', 'max:150'],
            'exclusions' => ['array'],
            'exclusions.*' => ['string', 'max:150'],
            'available_remedies' => ['required', 'array', 'min:1'],
            'available_remedies.*' => ['string', 'in:repair,replace,refund,exchange'],
            'coverage_extent' => ['required', 'string', 'in:full,percentage,fixed_amount,labour_only,parts_only'],
        ];
    }
}
