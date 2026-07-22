<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi;

use App\Application\Auth\AccessTokenValidator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ProductionDocsAccess
{
    public function __construct(private readonly AccessTokenValidator $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->isProduction()) {
            return $next($request);
        }

        $user = $this->tokens->authenticatedUser();
        abort_unless($user !== null, 401, 'A valid access token is required.');
        abort_unless($user->can('api.documentation.view'), 403);

        return $next($request);
    }
}
