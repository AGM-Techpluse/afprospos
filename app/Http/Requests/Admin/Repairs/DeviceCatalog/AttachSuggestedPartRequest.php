<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs\DeviceCatalog;

use Illuminate\Foundation\Http\FormRequest;

final class AttachSuggestedPartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku_id' => ['required', 'integer'],
        ];
    }
}
