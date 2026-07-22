<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Identity\UserStatus;
use Illuminate\Validation\Rule;

final class ChangeUserStatusRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(UserStatus::class)],
        ];
    }
}
