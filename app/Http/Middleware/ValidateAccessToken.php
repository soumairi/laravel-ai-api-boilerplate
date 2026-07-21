<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Identity\UserStatus;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

final readonly class ValidateAccessToken
{
    public function __construct(private JWTAuth $jwt) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = auth('api')->user();
        $payload = $this->jwt->parseToken()->getPayload();
        $version = (int) $payload->get('ver');
        $issuer = (string) $payload->get('iss');
        $audience = $payload->get('aud');
        $audiences = is_array($audience) ? $audience : [$audience];

        abort_unless(
            $user
                && $user->status === UserStatus::Active
                && $user->token_version === $version
                && hash_equals((string) config('jwt.issuer'), $issuer)
                && in_array((string) config('jwt.audience'), $audiences, true),
            401,
            'Access token is no longer valid.',
        );

        return $next($request);
    }
}
