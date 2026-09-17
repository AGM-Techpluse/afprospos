<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer\Warranty;

use Illuminate\Foundation\Http\FormRequest;

/** `customer_id` is never accepted from the client — the Controller forces it from the authenticated session (CPNC §0.2). */
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
            'originating_sale_id' => ['nullable', 'integer', 'required_without:originating_repair_job_id'],
            'originating_repair_job_id' => ['nullable', 'integer', 'required_without:originating_sale_id'],
        ];
    }
}
