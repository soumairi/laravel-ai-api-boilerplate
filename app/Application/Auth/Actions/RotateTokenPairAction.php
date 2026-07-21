<?php

declare(strict_types=1);

namespace App\Application\Auth\Actions;

use App\Application\Auth\ClientContext;
use App\Application\Auth\RefreshTokenService;
use App\Application\Auth\RotatedTokenPair;
use App\Application\Auth\TokenPair;
use App\Application\Auth\TokenService;
use App\Domain\Auth\TokenIdentifier;

final readonly class RotateTokenPairAction
{
    public function __construct(
        private TokenService $tokens,
        private RefreshTokenService $refreshTokens,
    ) {}

    public function execute(TokenIdentifier $identifier, ClientContext $context): RotatedTokenPair
    {
        $rotated = $this->refreshTokens->rotate($identifier, $context);
        $user = $rotated->user;

        return new RotatedTokenPair($user, new TokenPair(
            accessToken: $this->tokens->issueAccessToken($user),
            accessExpiresIn: $this->tokens->ttlSeconds(),
            refreshToken: $rotated->refreshToken->identifier->value,
            refreshExpiresAt: $rotated->refreshToken->expiresAt,
        ));
    }
}
