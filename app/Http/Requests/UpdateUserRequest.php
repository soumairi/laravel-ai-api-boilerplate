<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UpdateUserRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'email:rfc', 'max:254', Rule::unique('users')->ignore($this->route('user'))],
            'password' => ['sometimes', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ];
    }

    protected function additionalAllowedKeys(): array
    {
        return ['password_confirmation'];
    }
}
