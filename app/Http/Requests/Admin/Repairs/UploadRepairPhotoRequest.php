<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Repairs;

use Illuminate\Foundation\Http\FormRequest;

final class UploadRepairPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'max:8192'],
            'caption' => ['nullable', 'string', 'max:150'],
        ];
    }
}
