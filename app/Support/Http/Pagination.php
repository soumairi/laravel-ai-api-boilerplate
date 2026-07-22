<?php

declare(strict_types=1);

namespace App\Support\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class Pagination
{
    public static function perPage(Request $request, int $default): int
    {
        $validated = Validator::make($request->query(), [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ])->validate();

        return (int) ($validated['per_page'] ?? $default);
    }
}
