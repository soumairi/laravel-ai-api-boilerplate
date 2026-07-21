<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Auth\InvalidRefreshToken;
use App\Domain\Auth\RefreshTokenBusy;
use App\Domain\Auth\RefreshTokenReplay;
use App\Domain\Auth\TokenIdentifier;
use App\Models\RefreshSession;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RefreshTokenService
{
    public function create(User $user, ClientContext $context): RefreshToken
    {
        $identifier = TokenIdentifier::generate();
        $expiresAt = now()->addMinutes((int) config('api.refresh_ttl', 43200));

        RefreshSession::query()->create([
            'user_id' => $user->getKey(),
            'family_id' => (string) Str::ulid(),
            'token_hash' => $this->hash($identifier),
            'expires_at' => $expiresAt,
            'ip_address' => $context->ipAddress,
            'user_agent' => Str::limit((string) $context->userAgent, 512, ''),
        ]);

        return new RefreshToken($identifier, $expiresAt->toIso8601String());
    }

    public function rotate(TokenIdentifier $identifier, ClientContext $context): RotatedRefreshToken
    {
        $hash = $this->hash($identifier);
        /** @var RefreshSession|null $knownSession */
        $knownSession = RefreshSession::query()->where('token_hash', $hash)->first();
        if (! $knownSession) {
            throw new InvalidRefreshToken;
        }

        try {
            return Cache::lock("refresh-family:{$knownSession->family_id}", 10)->block(3, function () use ($hash, $context): RotatedRefreshToken {
                $result = DB::transaction(function () use ($hash, $context): RotatedRefreshToken|bool {
                    /** @var RefreshSession|null $session */
                    $session = RefreshSession::query()
                        ->where('token_hash', $hash)
                        ->lockForUpdate()
                        ->first();

                    if (! $session || $session->expires_at->isPast()) {
                        throw new InvalidRefreshToken;
                    }

                    if ($session->revoked_at) {
                        if ($session->revoke_reason !== 'rotated') {
                            throw new InvalidRefreshToken;
                        }

                        $this->revokeFamily($session, 'reuse_detected');
                        /** @var User $user */
                        $user = $session->user()->lockForUpdate()->firstOrFail();
                        $user->forceFill(['token_version' => $user->token_version + 1])->save();

                        return true;
                    }

                    $next = TokenIdentifier::generate();
                    $session->forceFill([
                        'last_used_at' => now(),
                        'revoked_at' => now(),
                        'revoke_reason' => 'rotated',
                    ])->save();
                    $replacement = RefreshSession::query()->create([
                        'user_id' => $session->user_id,
                        'family_id' => $session->family_id,
                        'token_hash' => $this->hash($next),
                        'previous_token_hash' => $session->token_hash,
                        'expires_at' => $session->expires_at,
                        'ip_address' => $context->ipAddress,
                        'user_agent' => Str::limit((string) $context->userAgent, 512, ''),
                    ]);

                    return new RotatedRefreshToken(
                        $replacement->user()->firstOrFail(),
                        new RefreshToken($next, $replacement->expires_at->toIso8601String()),
                    );
                }, 3);

                if ($result === true) {
                    throw new RefreshTokenReplay;
                }

                return $result;
            });
        } catch (LockTimeoutException) {
            throw new RefreshTokenBusy;
        }
    }

    public function revoke(TokenIdentifier $identifier, string $reason = 'logout'): void
    {
        RefreshSession::query()->where('token_hash', $this->hash($identifier))->update([
            'revoked_at' => now(),
            'revoke_reason' => $reason,
        ]);
    }

    public function revokeAll(User $user, string $reason = 'logout_all'): void
    {
        DB::transaction(function () use ($user, $reason): void {
            $user->refreshSessions()->whereNull('revoked_at')->update([
                'revoked_at' => now(),
                'revoke_reason' => $reason,
            ]);
            $user->forceFill(['token_version' => $user->token_version + 1])->save();
        });
    }

    private function revokeFamily(RefreshSession $session, string $reason): void
    {
        RefreshSession::query()->where('family_id', $session->family_id)->update([
            'revoked_at' => now(),
            'revoke_reason' => $reason,
        ]);
    }

    private function hash(TokenIdentifier $identifier): string
    {
        return $identifier->hash((string) config('app.key'));
    }
}
