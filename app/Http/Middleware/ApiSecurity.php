<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class ApiSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->headers->get('X-Request-ID');
        $requestId = is_string($requestId) && preg_match('/^[A-Za-z0-9._-]{8,128}$/', $requestId)
            ? $requestId
            : (string) Str::uuid();
        $request->attributes->set('request_id', $requestId);

        if ((int) $request->server('CONTENT_LENGTH', '0') > (int) config('api.max_request_bytes')) {
            return $this->problem($request, 413, 'Payload Too Large', 'Request body exceeds the configured limit.');
        }

        if ($request->isMethodSafe() === false && ! $request->isJson()) {
            return $this->problem($request, 415, 'Unsupported Media Type', 'Content-Type must be application/json.');
        }

        if (! $request->expectsJson() && ! $request->is('health/*')) {
            return $this->problem($request, 406, 'Not Acceptable', 'Accept must allow application/json.');
        }

        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    private function problem(Request $request, int $status, string $title, string $detail): JsonResponse
    {
        return response()->json([
            'type' => 'about:blank',
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => $request->path(),
            'request_id' => $request->attributes->get('request_id'),
        ], $status, ['Content-Type' => 'application/problem+json']);
    }
}
