<?php

declare(strict_types=1);

use App\Application\Auth\TokenClaims;
use App\Application\Auth\TokenPair;
use App\Domain\Auth\TokenIdentifier;
use App\Domain\Identity\UserStatus;
use App\Models\User;

it('creates immutable opaque token identifiers and deterministic hashes', function (): void {
    $identifier = TokenIdentifier::generate();

    expect(strlen($identifier->value))->toBeGreaterThanOrEqual(64)
        ->and($identifier->hash('test-key'))->toBe(hash_hmac('sha256', $identifier->value, 'test-key'))
        ->and(fn () => TokenIdentifier::fromString('short'))->toThrow(InvalidArgumentException::class);
});

it('maps explicit access-token claims and token pairs', function (): void {
    config(['jwt.issuer' => 'issuer.test', 'jwt.audience' => 'audience.test']);
    $user = new User;
    $user->forceFill(['token_version' => 7, 'status' => UserStatus::Active]);

    expect(TokenClaims::forUser($user)->toArray())->toBe([
        'iss' => 'issuer.test',
        'aud' => 'audience.test',
        'ver' => 7,
        'status' => 'active',
    ]);

    $pair = new TokenPair('access', 900, 'refresh', '2030-01-01T00:00:00+00:00');
    expect($pair->accessToken)->toBe('access')
        ->and($pair->refreshToken)->toBe('refresh');
});
