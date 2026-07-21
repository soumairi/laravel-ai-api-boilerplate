<?php

declare(strict_types=1);

namespace App\Infrastructure\OpenApi;

use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

final class BearerSecurityStrategy extends MiddlewareAuthSecurityStrategy
{
    public function __construct()
    {
        parent::__construct(
            middleware: ['auth:api'],
            scheme: SecurityScheme::http('bearer', 'JWT')->as('bearerAuth'),
        );
    }
}
