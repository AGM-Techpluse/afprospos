<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Warranty;

use Illuminate\Foundation\Http\FormRequest;

final class CreateWarrantyClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warranty_policy_id' => ['required', 'integer', 'exists:warranty_policies,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'originating_sale_id' => ['nullable', 'integer', 'required_without:originating_repair_job_id'],
            'originating_repair_job_id' => ['nullable', 'integer', 'required_without:originating_sale_id'],
        ];
    }
}
