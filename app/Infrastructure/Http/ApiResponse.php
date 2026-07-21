<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /** @param mixed $data */
    public static function success($data = null, int $status = 200, array $meta = []): JsonResponse
    {
        $payload = ['data' => $data, 'request_id' => request()->attributes->get('request_id')];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }
}
