<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class EmailRequest extends AuthRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:254'],
        ];
    }
}
