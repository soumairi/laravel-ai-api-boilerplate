<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;

final readonly class AccessTokenValidator
{
    public function __construct(private JWTAuth $jwt) {}

    public function authenticatedUser(): ?User
    {
        try {
            $jwt = $this->jwt->parseToken();
            $user = $jwt->authenticate();
            if (! $user instanceof User) {
                return null;
            }

            $payload = $jwt->getPayload();
            $audience = $payload->get('aud');
            $audiences = is_array($audience) ? $audience : [$audience];

            if (! $user->status->mayAuthenticate()
                || ! hash_equals((string) $user->getAuthIdentifier(), (string) $payload->get('sub'))
                || $user->token_version !== (int) $payload->get('ver')
                || ! hash_equals((string) config('jwt.issuer'), (string) $payload->get('iss'))
                || ! in_array((string) config('jwt.audience'), $audiences, true)) {
                return null;
            }

            return $user;
        } catch (JWTException) {
            return null;
        }
    }
}
