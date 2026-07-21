<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class AuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return match ($this->route()->getName()) {
            'auth.register' => [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email:rfc', 'max:254', 'unique:users,email'],
                'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()->uncompromised()],
            ],
            'auth.login' => [
                'email' => ['required', 'email:rfc', 'max:254'],
                'password' => ['required', 'string', 'max:1024'],
            ],
            'auth.refresh', 'auth.logout' => ['refresh_token' => ['required', 'string', 'min:64', 'max:256']],
            'auth.forgot-password', 'auth.verify.resend' => ['email' => ['required', 'email:rfc', 'max:254']],
            'auth.reset-password' => [
                'email' => ['required', 'email:rfc', 'max:254'],
                'token' => ['required', 'string'],
                'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()->uncompromised()],
            ],
            'auth.change-password' => [
                'current_password' => ['required', 'current_password:api'],
                'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()->uncompromised(), 'different:current_password'],
            ],
            default => [],
        };
    }
}
