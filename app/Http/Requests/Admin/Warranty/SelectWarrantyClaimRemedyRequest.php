<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Warranty;

use Illuminate\Foundation\Http\FormRequest;

final class SelectWarrantyClaimRemedyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 'exchange' is a valid BLD remedy but unsupported until Trade-In ships — rejected here with a clear message rather than a generic "invalid remedy" 422 (defense in depth; the Handler also rejects it).
            'remedy' => ['required', 'string', 'in:repair,replace,refund'],
        ];
    }

    public function messages(): array
    {
        return [
            'remedy.in' => 'That remedy is not yet supported — exchange is handled by the Trade-In flow, which has not shipped yet.',
        ];
    }
}
