<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Collection;

use Illuminate\Foundation\Http\FormRequest;

final class ExtendDeadlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_deadline_at' => ['required', 'date', 'after:now'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
