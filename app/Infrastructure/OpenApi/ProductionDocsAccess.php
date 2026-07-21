<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ProductionDocsAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->isProduction()) {
            return $next($request);
        }

        $user = auth('api')->user();
        abort_unless($user?->can('api.documentation.view') === true, 403);

        return $next($request);
    }
}
