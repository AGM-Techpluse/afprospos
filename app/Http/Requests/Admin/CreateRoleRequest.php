<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Domain\RBAC\Application\Queries\AvailableRolesQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in(app(AvailableRolesQuery::class)->assignablePermissionNames())],
        ];
    }
}
