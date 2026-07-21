<?php

declare(strict_types=1);

use App\Console\Commands\BoilerplateInitCommand;
use App\Domain\Auth\InvalidRefreshToken;
use App\Domain\Auth\RefreshTokenBusy;
use App\Domain\Auth\RefreshTokenReplay;
use App\Http\Middleware\ApiSecurity;
use App\Http\Middleware\ValidateAccessToken;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands([
        BoilerplateInitCommand::class,
    ])
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        then: static function (): void {
            Route::middleware('api')->group(base_path('routes/health.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: array_filter(explode(',', (string) env('TRUSTED_PROXIES', ''))),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
        $middleware->appendToGroup('api', ApiSecurity::class);
        $middleware->alias(['token.valid' => ValidateAccessToken::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*') && ! $request->is('health/*')) {
                return null;
            }

            $status = match (true) {
                $exception instanceof ValidationException => 422,
                $exception instanceof AuthenticationException => 401,
                $exception instanceof AuthorizationException => 403,
                $exception instanceof ModelNotFoundException => 404,
                $exception instanceof InvalidRefreshToken, $exception instanceof RefreshTokenReplay => 401,
                $exception instanceof JWTException => 401,
                $exception instanceof RefreshTokenBusy => 409,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => 500,
            };
            $titles = [
                400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden',
                404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable',
                409 => 'Conflict', 415 => 'Unsupported Media Type', 422 => 'Unprocessable Content',
                429 => 'Too Many Requests', 500 => 'Internal Server Error', 503 => 'Service Unavailable',
            ];
            $detail = $status >= 500
                ? 'An unexpected error occurred.'
                : ($exception->getMessage() ?: ($titles[$status] ?? 'Request failed.'));
            $problem = [
                'type' => 'about:blank',
                'title' => $titles[$status] ?? Response::$statusTexts[$status] ?? 'Request Failed',
                'status' => $status,
                'detail' => $detail,
                'instance' => $request->path(),
                'request_id' => $request->attributes->get('request_id'),
            ];
            if ($exception instanceof ValidationException) {
                $problem['errors'] = $exception->errors();
            }

            $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];
            $headers['Content-Type'] = 'application/problem+json';

            return response()->json($problem, $status, $headers);
        });
        $exceptions->respond(function (Response $response): Response {
            if (request()->is('api/*') || request()->is('health/*')) {
                $response->headers->set('X-Request-ID', (string) request()->attributes->get('request_id'));
                $response->headers->set('X-Content-Type-Options', 'nosniff');
                $response->headers->set('X-Frame-Options', 'DENY');
                $response->headers->set('Referrer-Policy', 'no-referrer');
                $response->headers->set('Cache-Control', 'no-store');
            }

            return $response;
        });
    })->create();
