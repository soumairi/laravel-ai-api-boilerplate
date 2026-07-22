<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class RefreshTokenRequest extends AuthRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'refresh_token' => ['required', 'string', 'min:64', 'max:256'],
        ];
    }
}
