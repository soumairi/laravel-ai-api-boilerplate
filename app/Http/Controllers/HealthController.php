<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class HealthController extends Controller
{
    public function live(): JsonResponse
    {
        return ApiResponse::success(['status' => 'ok']);
    }

    public function ready(): JsonResponse
    {
        try {
            DB::select('select 1');
            Redis::connection()->ping();

            return ApiResponse::success(['status' => 'ready']);
        } catch (Throwable) {
            return response()->json([
                'type' => 'about:blank',
                'title' => 'Service Unavailable',
                'status' => 503,
                'detail' => 'A required dependency is unavailable.',
                'instance' => request()->path(),
                'request_id' => request()->attributes->get('request_id'),
            ], 503, ['Content-Type' => 'application/problem+json']);
        }
    }
}
