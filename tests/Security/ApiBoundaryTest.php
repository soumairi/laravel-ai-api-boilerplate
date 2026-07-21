<?php

declare(strict_types=1);

use App\Application\Auth\TokenService;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;

it('requires JSON content for unsafe API requests', function (): void {
    $response = $this->call(
        'POST',
        '/api/v1/auth/login',
        ['email' => 'user@example.test', 'password' => 'secret'],
        server: ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
    );

    $this->assertProblem($response, 415);
});

it('requires an acceptable JSON representation', function (): void {
    $response = $this->withHeader('Accept', 'text/html')->get('/api/v1/auth/registration');

    $this->assertProblem($response, 406);
});

it('rejects oversized request bodies before controller execution', function (): void {
    config(['api.max_request_bytes' => 32]);

    $response = $this->withServerVariables(['CONTENT_LENGTH' => 1024])
        ->postJson('/api/v1/auth/login', ['email' => 'user@example.test', 'password' => 'secret']);

    $this->assertProblem($response, 413);
});

it('does not trust malformed request identifiers', function (): void {
    $response = $this->withHeaders([
        'Accept' => 'application/json',
        'X-Request-ID' => '<script>alert(1)</script>',
    ])->getJson('/api/v1/auth/registration');

    $response->assertOk();
    expect($response->headers->get('X-Request-ID'))->not->toBe('<script>alert(1)</script>')
        ->and($response->headers->get('Content-Security-Policy'))->toContain("default-src 'none'");
});

it('protects authenticated routes with bearer JWT', function (): void {
    $this->assertProblem($this->getJson('/api/v1/auth/me'), 401);
});

it('rejects access tokens after global token-version revocation', function (): void {
    $user = User::factory()->create();
    $token = app(TokenService::class)->issueAccessToken($user);
    $user->increment('token_version');

    $response = $this->withHeaders($this->bearer($token))->getJson('/api/v1/auth/me');

    $this->assertProblem($response, 401);
});

it('rejects access tokens for the wrong issuer or audience', function (string $claim, string $value): void {
    $user = User::factory()->create();
    $token = app(JWTAuth::class)->claims([
        'iss' => (string) config('jwt.issuer'),
        'aud' => (string) config('jwt.audience'),
        $claim => $value,
    ])->fromUser($user);

    $this->assertProblem($this->withHeaders($this->bearer($token))->getJson('/api/v1/auth/me'), 401);
})->with([
    'issuer' => ['iss', 'https://wrong-issuer.example'],
    'audience' => ['aud', 'wrong-audience'],
]);

it('rate limits repeated login attempts', function (): void {
    RateLimiter::clear(md5('loginrate-limit@example.test|127.0.0.1'));

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'rate-limit@example.test',
            'password' => 'invalid',
        ])->assertUnauthorized();
    }

    $this->assertProblem($this->postJson('/api/v1/auth/login', [
        'email' => 'rate-limit@example.test',
        'password' => 'invalid',
    ]), 429)->assertHeader('Retry-After');
});

it('uses configurable login rate limits', function (): void {
    config([
        'api.rate_limits.login_identity_per_minute' => 2,
    ]);
    RateLimiter::clear(md5('loginconfigured-rate-limit@example.test|127.0.0.1'));

    foreach (range(1, 2) as $attempt) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'configured-rate-limit@example.test',
            'password' => 'invalid',
        ])->assertUnauthorized();
    }

    $this->assertProblem($this->postJson('/api/v1/auth/login', [
        'email' => 'configured-rate-limit@example.test',
        'password' => 'invalid',
    ]), 429);
});
