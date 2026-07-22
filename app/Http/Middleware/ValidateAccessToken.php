<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Auth\AccessTokenValidator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ValidateAccessToken
{
    public function __construct(private AccessTokenValidator $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->tokens->authenticatedUser() !== null, 401, 'Access token is no longer valid.');

        return $next($request);
    }
}
