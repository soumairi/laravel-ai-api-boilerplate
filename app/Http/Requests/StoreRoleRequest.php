<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class StoreRoleRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('roles')],
            'permissions' => ['sometimes', 'array', 'max:100'],
            'permissions.*' => ['string', 'distinct', 'exists:permissions,name'],
        ];
    }
}
