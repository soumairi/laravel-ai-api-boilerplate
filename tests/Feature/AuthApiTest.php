<?php

declare(strict_types=1);

use App\Application\Auth\ClientContext;
use App\Application\Auth\RefreshTokenService;
use App\Domain\Identity\UserStatus;
use App\Models\RefreshSession;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

it('returns registration policy and request correlation headers', function (): void {
    config(['api.registration_enabled' => true, 'api.require_email_verification' => true]);

    $response = $this->withHeaders([
        'Accept' => 'application/json',
        'X-Request-ID' => 'test-request-1234',
    ])->getJson('/api/v1/auth/registration');

    $this->assertApiSuccess($response)
        ->assertHeader('X-Request-ID', 'test-request-1234')
        ->assertJsonPath('data.enabled', true)
        ->assertJsonPath('data.require_email_verification', true);
});

it('registers a pending user and queues verification', function (): void {
    Notification::fake();
    $this->seed(DatabaseSeeder::class);
    config(['api.registration_enabled' => true, 'api.require_email_verification' => true]);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'API User',
        'email' => 'API.USER@example.test',
        'password' => 'V3ry-Str0ng!Passphrase',
        'password_confirmation' => 'V3ry-Str0ng!Passphrase',
    ]);

    $this->assertApiSuccess($response, 201)
        ->assertJsonPath('data.email', 'api.user@example.test')
        ->assertJsonPath('data.status', UserStatus::Pending->value)
        ->assertJsonMissingPath('data.password');

    $this->assertDatabaseHas('users', ['email' => 'api.user@example.test', 'status' => 'pending']);
    expect(User::query()->where('email', 'api.user@example.test')->firstOrFail()->hasRole('user'))->toBeTrue();
    Notification::assertCount(1);
});

it('rejects invalid credentials without leaking which field failed', function (): void {
    User::factory()->create(['email' => 'known@example.test']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'known@example.test',
        'password' => 'wrong-password',
    ]);

    $this->assertProblem($response, 401)
        ->assertJsonPath('detail', 'Invalid credentials.');
});

it('issues an RS256 access token and an opaque refresh token', function (): void {
    $user = User::factory()->create([
        'email' => 'login@example.test',
        'password' => Hash::make('correct-password'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $this->assertApiSuccess($response)
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.expires_in', 900)
        ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'refresh_expires_at', 'user']]);

    $parts = explode('.', $response->json('data.access_token'));
    $header = json_decode((string) base64_decode(strtr($parts[0], '-_', '+/')), true, flags: JSON_THROW_ON_ERROR);
    expect($parts)->toHaveCount(3)
        ->and($header['alg'])->toBe('RS256')
        ->and(strlen((string) $response->json('data.refresh_token')))->toBeGreaterThanOrEqual(64);

    $this->assertDatabaseCount('refresh_sessions', 1);
    $this->assertDatabaseMissing('refresh_sessions', ['token_hash' => $response->json('data.refresh_token')]);
});

it('returns validation failures as problem details', function (): void {
    $response = $this->postJson('/api/v1/auth/login', ['email' => 'not-an-email']);

    $this->assertProblem($response, 422)
        ->assertJsonStructure(['errors' => ['email', 'password']]);
});

it('rejects unknown authentication fields', function (): void {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'known@example.test',
        'password' => 'password',
        'is_admin' => true,
    ]);

    $this->assertProblem($response, 422)
        ->assertJsonStructure(['errors' => ['is_admin']]);
});

it('hides registration when it is disabled', function (): void {
    config(['api.registration_enabled' => false]);

    $this->assertProblem($this->postJson('/api/v1/auth/register', [
        'name' => 'Disabled Registration',
        'email' => 'disabled@example.test',
        'password' => 'V3ry-Str0ng!Passphrase',
        'password_confirmation' => 'V3ry-Str0ng!Passphrase',
    ]), 404);
});

it('rejects users whose account is not active', function (UserStatus $status): void {
    $user = User::factory()->create([
        'status' => $status,
        'password' => Hash::make('correct-password'),
    ]);

    $this->assertProblem($this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]), 403);
})->with([UserStatus::Pending, UserStatus::Suspended]);

it('refreshes and logs out an authenticated session', function (): void {
    $user = User::factory()->create(['password' => Hash::make('correct-password')]);
    $login = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);
    $access = $login->json('data.access_token');
    $refresh = $login->json('data.refresh_token');

    $this->assertApiSuccess($this->postJson('/api/v1/auth/refresh', ['refresh_token' => $refresh]));
    $this->assertApiSuccess($this->withHeaders($this->bearer($access))->getJson('/api/v1/auth/me'));
    $this->withHeaders($this->bearer($access))->postJson('/api/v1/auth/logout', [
        'refresh_token' => $refresh,
    ])->assertNoContent();

    expect(RefreshSession::query()->where('token_hash', hash_hmac('sha256', $refresh, (string) config('app.key')))
        ->firstOrFail()->revoked_at)->not->toBeNull();
    $this->assertProblem($this->withHeaders($this->bearer($access))->getJson('/api/v1/auth/me'), 401);
});

it('changes a password and revokes every session', function (): void {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);
    $login = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'old-password',
    ]);

    $this->assertApiSuccess($this->withHeaders($this->bearer($login->json('data.access_token')))
        ->patchJson('/api/v1/auth/password', [
            'current_password' => 'old-password',
            'password' => 'N3w-Very-Str0ng!Passphrase',
            'password_confirmation' => 'N3w-Very-Str0ng!Passphrase',
        ]));

    expect(Hash::check('N3w-Very-Str0ng!Passphrase', $user->refresh()->password))->toBeTrue()
        ->and($user->token_version)->toBe(2)
        ->and(RefreshSession::query()->whereNull('revoked_at')->count())->toBe(0);
});

it('queues non-enumerating recovery and verification responses', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email_verified_at' => null]);

    $this->assertApiSuccess($this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'missing@example.test',
    ]), 202);
    $this->assertApiSuccess($this->postJson('/api/v1/auth/email/verification-notification', [
        'email' => $user->email,
    ]), 202);
    Notification::assertCount(1);
});

it('verifies an email through a valid signed URL', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Pending,
        'email_verified_at' => null,
    ]);
    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(10),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())],
    );

    $this->assertApiSuccess($this->getJson($url));
    expect($user->refresh()->hasVerifiedEmail())->toBeTrue()
        ->and($user->status)->toBe(UserStatus::Active);
});

it('does not reactivate a suspended account when its email is verified', function (): void {
    $user = User::factory()->create([
        'status' => UserStatus::Suspended,
        'email_verified_at' => null,
    ]);
    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(10),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())],
    );

    $this->assertApiSuccess($this->getJson($url));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue()
        ->and($user->status)->toBe(UserStatus::Suspended);
});

it('resets a password and revokes active sessions', function (): void {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);
    app(RefreshTokenService::class)->create($user, new ClientContext(null, null));
    $resetToken = Password::broker()->createToken($user);

    $this->assertApiSuccess($this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'token' => $resetToken,
        'password' => 'R3set-Very-Str0ng!Passphrase',
        'password_confirmation' => 'R3set-Very-Str0ng!Passphrase',
    ]));

    expect(Hash::check('R3set-Very-Str0ng!Passphrase', $user->refresh()->password))->toBeTrue()
        ->and($user->token_version)->toBe(2)
        ->and(RefreshSession::query()->whereNull('revoked_at')->count())->toBe(0);
});
