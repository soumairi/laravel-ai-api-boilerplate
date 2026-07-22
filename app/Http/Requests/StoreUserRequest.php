<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Identity\UserStatus;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class StoreUserRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:254', Rule::unique('users')],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
            'status' => ['sometimes', Rule::enum(UserStatus::class)],
            'roles' => ['sometimes', 'array', 'max:20'],
            'roles.*' => ['string', 'distinct', 'exists:roles,name'],
        ];
    }

    protected function additionalAllowedKeys(): array
    {
        return ['password_confirmation'];
    }
}
