<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Collection;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TransferCollectionCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_shop_id' => ['required', 'integer', Rule::exists('shops', 'id')->where('status', 'active')],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
