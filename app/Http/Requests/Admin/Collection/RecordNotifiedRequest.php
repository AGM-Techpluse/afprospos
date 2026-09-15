<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Collection;

use Illuminate\Foundation\Http\FormRequest;

final class RecordNotifiedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
