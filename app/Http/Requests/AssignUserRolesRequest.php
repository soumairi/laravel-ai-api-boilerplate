<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class AssignUserRolesRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'max:20'],
            'roles.*' => ['string', 'distinct', 'exists:roles,name'],
        ];
    }
}
