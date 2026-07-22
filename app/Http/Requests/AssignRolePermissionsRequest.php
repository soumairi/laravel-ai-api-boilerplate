<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class AssignRolePermissionsRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array', 'max:100'],
            'permissions.*' => ['string', 'distinct', 'exists:permissions,name'],
        ];
    }
}
