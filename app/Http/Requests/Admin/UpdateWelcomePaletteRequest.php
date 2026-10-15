<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Domain\Shared\Application\Queries\WelcomePaletteQuery;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateWelcomePaletteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $known = array_keys(WelcomePaletteQuery::defaults());

        return [
            'palette.*' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'palette' => [
                'required',
                'array',
                function (string $attribute, mixed $value, \Closure $fail) use ($known): void {
                    foreach (array_keys((array) $value) as $token) {
                        if (! in_array($token, $known, true)) {
                            $fail("Unknown colour [{$token}].");
                        }
                    }
                },
            ],
        ];
    }

    /** @return array<string, string> */
    public function palette(): array
    {
        return array_map('strval', (array) $this->input('palette', []));
    }
}
