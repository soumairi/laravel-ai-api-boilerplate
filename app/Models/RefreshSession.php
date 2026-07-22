<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $family_id
 * @property string $user_id
 * @property string $token_hash
 * @property string|null $previous_token_hash
 * @property string|null $revoke_reason
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $revoked_at
 */
class RefreshSession extends Model
{
    use HasFactory, HasUlids, MassPrunable;

    /** @var list<string> */
    protected $fillable = [
        'user_id', 'family_id', 'token_hash', 'previous_token_hash', 'expires_at',
        'last_used_at', 'revoked_at', 'revoke_reason', 'ip_address', 'user_agent',
    ];

    /** @var list<string> */
    protected $hidden = ['token_hash', 'previous_token_hash'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return Builder<RefreshSession> */
    public function prunable(): Builder
    {
        $cutoff = now()->subDays((int) config('api.refresh_session_retention_days'));

        return self::query()->where(
            static fn (Builder $query): Builder => $query
                ->where('expires_at', '<', $cutoff)
                ->orWhere('revoked_at', '<', $cutoff),
        );
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'last_used_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
