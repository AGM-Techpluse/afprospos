<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Domain\RBAC\Application\Queries\AvailableRolesQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in(app(AvailableRolesQuery::class)->assignablePermissionNames())],
        ];
    }
}
