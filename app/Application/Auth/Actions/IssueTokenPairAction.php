<?php

declare(strict_types=1);

namespace App\Application\Auth\Actions;

use App\Application\Auth\ClientContext;
use App\Application\Auth\RefreshTokenService;
use App\Application\Auth\TokenPair;
use App\Application\Auth\TokenService;
use App\Models\User;

final readonly class IssueTokenPairAction
{
    public function __construct(
        private TokenService $tokens,
        private RefreshTokenService $refreshTokens,
    ) {}

    public function execute(User $user, ClientContext $context): TokenPair
    {
        $refreshToken = $this->refreshTokens->create($user, $context);

        return new TokenPair(
            accessToken: $this->tokens->issueAccessToken($user),
            accessExpiresIn: $this->tokens->ttlSeconds(),
            refreshToken: $refreshToken->identifier->value,
            refreshExpiresAt: $refreshToken->expiresAt,
        );
    }
}
