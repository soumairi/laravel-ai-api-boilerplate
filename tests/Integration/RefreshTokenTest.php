<?php

declare(strict_types=1);

use App\Application\Auth\ClientContext;
use App\Application\Auth\RefreshTokenService;
use App\Application\Auth\TokenService;
use App\Domain\Auth\RefreshTokenReplay;
use App\Models\RefreshSession;
use App\Models\User;

it('rotates refresh tokens atomically and preserves their family', function (): void {
    $user = User::factory()->create();
    $context = new ClientContext('127.0.0.1', 'Pest');
    $service = app(RefreshTokenService::class);
    $first = $service->create($user, $context);
    $rotated = $service->rotate($first->identifier, $context);

    expect($rotated->refreshToken->identifier->value)->not->toBe($first->identifier->value)
        ->and(RefreshSession::query()->count())->toBe(2)
        ->and(RefreshSession::query()->where('revoke_reason', 'rotated')->count())->toBe(1)
        ->and(RefreshSession::query()->distinct('family_id')->count('family_id'))->toBe(1);
});

it('detects refresh replay, revokes the family, and invalidates access tokens', function (): void {
    $user = User::factory()->create();
    $context = new ClientContext(null, null);
    $refresh = app(RefreshTokenService::class);
    $first = $refresh->create($user, $context);
    $access = app(TokenService::class)->issueAccessToken($user);
    $refresh->rotate($first->identifier, $context);

    expect(fn () => $refresh->rotate($first->identifier, $context))->toThrow(RefreshTokenReplay::class);

    expect($user->refresh()->token_version)->toBe(2)
        ->and(RefreshSession::query()->whereNull('revoked_at')->count())->toBe(0)
        ->and(RefreshSession::query()->where('revoke_reason', 'reuse_detected')->count())->toBe(2);

    $this->assertProblem(
        $this->withHeaders($this->bearer($access))->getJson('/api/v1/auth/me'),
        401,
    );
});

it('rejects unknown and expired refresh tokens', function (): void {
    $this->assertProblem($this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => str_repeat('a', 86),
    ]), 401);

    $user = User::factory()->create();
    $created = app(RefreshTokenService::class)->create($user, new ClientContext(null, null));
    RefreshSession::query()->update(['expires_at' => now()->subMinute()]);

    $this->assertProblem($this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $created->identifier->value,
    ]), 401);
});

it('revokes all refresh sessions and existing access tokens', function (): void {
    $user = User::factory()->create();
    $context = new ClientContext(null, null);
    $refresh = app(RefreshTokenService::class);
    $refresh->create($user, $context);
    $refresh->create($user, $context);
    $access = app(TokenService::class)->issueAccessToken($user);

    $refresh->revokeAll($user);

    expect(RefreshSession::query()->whereNull('revoked_at')->count())->toBe(0)
        ->and($user->refresh()->token_version)->toBe(2);
    $this->assertProblem($this->withHeaders($this->bearer($access))->getJson('/api/v1/auth/me'), 401);
});
