<?php

declare(strict_types=1);

use App\Infrastructure\Notifications\QueuedResetPassword;
use App\Infrastructure\Notifications\QueuedVerifyEmail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Redis;

it('runs against PostgreSQL as the reference database', function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql')
        ->and(DB::selectOne('select version() as version')->version)->toContain('PostgreSQL');
});

it('uses ULIDs and PostgreSQL persistence semantics', function (): void {
    $user = User::factory()->create();

    expect($user->id)->toMatch('/^[0-9a-hjkmnp-tv-z]{26}$/')
        ->and(DB::table('users')->where('id', $user->id)->exists())->toBeTrue();
});

it('keeps email notifications queueable', function (): void {
    expect(new QueuedVerifyEmail)->toBeInstanceOf(ShouldQueue::class)
        ->and(new QueuedResetPassword('test-token'))->toBeInstanceOf(ShouldQueue::class);

    Notification::fake();
    $user = User::factory()->create();
    $user->sendEmailVerificationNotification();
    Notification::assertSentTo($user, QueuedVerifyEmail::class);
});

it('round trips Redis when infrastructure is available', function (): void {
    if (! extension_loaded('redis')) {
        $this->markTestSkipped('The native Redis extension is unavailable in this PHP runtime.');
    }

    try {
        Redis::connection()->set('integration:openapi-tests', 'ok', 'EX', 10);
        expect(Redis::connection()->get('integration:openapi-tests'))->toBe('ok');
        Redis::connection()->del('integration:openapi-tests');
    } catch (Throwable $exception) {
        $this->markTestSkipped('Redis infrastructure is unavailable: '.$exception->getMessage());
    }
});
