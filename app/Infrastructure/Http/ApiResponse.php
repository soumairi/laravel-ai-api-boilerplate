<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /** @param mixed $data */
    public static function success($data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'request_id' => request()->attributes->get('request_id'),
        ], $status);
    }

    /**
     * @param  mixed  $data
     * @param  array<string, int>  $meta
     */
    public static function paginated($data, array $meta): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'request_id' => request()->attributes->get('request_id'),
            'meta' => $meta,
        ]);
    }
}
