<?php

declare(strict_types=1);

use App\Infrastructure\Notifications\QueuedResetPassword;
use App\Infrastructure\Notifications\QueuedVerifyEmail;
use App\Models\RefreshSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('prunes only refresh sessions beyond the configured retention window', function (): void {
    config(['api.refresh_session_retention_days' => 30]);
    $user = User::factory()->create();

    $expired = RefreshSession::query()->create([
        'user_id' => $user->id,
        'family_id' => (string) Str::ulid(),
        'token_hash' => hash('sha256', 'expired'),
        'expires_at' => now()->subDays(31),
    ]);
    $oldRevoked = RefreshSession::query()->create([
        'user_id' => $user->id,
        'family_id' => (string) Str::ulid(),
        'token_hash' => hash('sha256', 'old-revoked'),
        'expires_at' => now()->addDay(),
        'revoked_at' => now()->subDays(31),
    ]);
    $recentRevoked = RefreshSession::query()->create([
        'user_id' => $user->id,
        'family_id' => (string) Str::ulid(),
        'token_hash' => hash('sha256', 'recent-revoked'),
        'expires_at' => now()->addDay(),
        'revoked_at' => now()->subDays(2),
    ]);
    $active = RefreshSession::query()->create([
        'user_id' => $user->id,
        'family_id' => (string) Str::ulid(),
        'token_hash' => hash('sha256', 'active'),
        'expires_at' => now()->addDay(),
    ]);

    expect((new RefreshSession)->prunable()->pluck('id')->all())
        ->toEqualCanonicalizing([$expired->id, $oldRevoked->id])
        ->not->toContain($recentRevoked->id, $active->id);
});

it('configures bounded retries for queued authentication notifications', function (): void {
    foreach ([new QueuedVerifyEmail, new QueuedResetPassword('test-token')] as $notification) {
        expect($notification->queue)->toBe('notifications')
            ->and($notification->tries)->toBe(3)
            ->and($notification->timeout)->toBe(30)
            ->and($notification->afterCommit)->toBeTrue()
            ->and($notification->backoff())->toBe([10, 60, 300]);
    }
});

it('indexes refresh-session revocation lookups by user', function (): void {
    /** @var object{indexdef: string}|null $index */
    $index = DB::selectOne(
        "select indexdef from pg_indexes where schemaname = current_schema() and indexname = 'refresh_sessions_user_revoked_index'",
    );

    expect($index)->not->toBeNull()
        ->and($index->indexdef)->toContain('(user_id, revoked_at)');
});
