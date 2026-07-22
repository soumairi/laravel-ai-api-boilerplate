<?php

declare(strict_types=1);

use App\Application\Auth\TokenService;
use App\Domain\Identity\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

function exportedOpenApi(): array
{
    $path = sys_get_temp_dir().'/laravel-api-openapi-'.getmypid().'.json';
    Artisan::call('scramble:export', ['--path' => $path, '--no-interaction' => true]);

    expect(Artisan::output())->not->toContain('ERROR')
        ->and($path)->toBeFile();

    return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
}

it('keeps the tracked OpenAPI artifact synchronized', function (): void {
    $document = exportedOpenApi();
    $tracked = json_decode(
        (string) file_get_contents(base_path('docs/openapi/openapi.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($document)->toBe($tracked);
});

it('renders the Scalar API reference outside production', function (): void {
    $this->get('/docs/api')
        ->assertOk()
        ->assertSee('@scalar/api-reference', escape: false);
});

it('protects documentation in production', function (): void {
    app()->detectEnvironment(static fn (): string => 'production');

    try {
        $this->get('/docs/api')->assertUnauthorized();
        $this->get('/docs/api.json')->assertUnauthorized();
    } finally {
        app()->detectEnvironment(static fn (): string => 'testing');
    }
});

it('authorizes production documentation separately from Horizon', function (): void {
    Permission::findOrCreate('api.documentation.view', 'api');
    Permission::findOrCreate('horizon.view', 'api');
    $docsUser = User::factory()->create();
    $docsUser->givePermissionTo('api.documentation.view');
    $horizonUser = User::factory()->create();
    $horizonUser->givePermissionTo('horizon.view');
    app()->detectEnvironment(static fn (): string => 'production');

    try {
        $docsHeaders = $this->bearer(app(TokenService::class)->issueAccessToken($docsUser));
        $horizonHeaders = $this->bearer(app(TokenService::class)->issueAccessToken($horizonUser));

        $this->withHeaders($docsHeaders)->get('/docs/api')->assertOk();
        $this->withHeaders($horizonHeaders)->get('/docs/api')->assertForbidden();
        $this->withHeaders($horizonHeaders)->get('/horizon')->assertOk();
        $this->withHeaders($docsHeaders)->get('/horizon')->assertForbidden();

        $docsUser->increment('token_version');
        $this->withHeaders($docsHeaders)->get('/docs/api')->assertUnauthorized();

        $horizonUser->forceFill(['status' => UserStatus::Suspended])->save();
        $this->withHeaders($horizonHeaders)->get('/horizon')->assertForbidden();
    } finally {
        app()->detectEnvironment(static fn (): string => 'testing');
    }
});

it('exports valid OpenAPI with JWT bearer security and typed operations', function (): void {
    $document = exportedOpenApi();

    expect($document['openapi'])->toStartWith('3.')
        ->and($document['info']['version'])->toBe('1.0.0')
        ->and($document['components']['securitySchemes']['bearerAuth'])->toMatchArray([
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
        ])
        ->and($document['paths']['/v1/auth/login']['post']['security'])->toBe([])
        ->and($document['paths']['/v1/auth/me']['get'])->not->toHaveKey('security')
        ->and($document['security'][0])->toHaveKey('bearerAuth');

    foreach ($document['paths'] as $path => $operations) {
        foreach ($operations as $method => $operation) {
            expect($operation)->toHaveKey('responses');
            foreach ($operation['responses'] as $response) {
                if (isset($response['content']['application/json'])) {
                    expect($response['content']['application/json'])->toHaveKey('schema');
                }
            }
            foreach ($operation['responses'] as $status => $response) {
                if ((int) $status >= 400) {
                    expect($response['content'])->toHaveKey('application/problem+json');
                }
            }
        }
    }
});

it('documents route-specific requests problem details and empty 204 responses', function (): void {
    $document = exportedOpenApi();
    $schemas = $document['components']['schemas'];

    expect($schemas)->toHaveKeys([
        'RegisterRequest',
        'LoginRequest',
        'RefreshTokenRequest',
        'EmailRequest',
        'ResetPasswordRequest',
        'ChangePasswordRequest',
        'StoreUserRequest',
        'UpdateUserRequest',
        'ChangeUserStatusRequest',
        'AssignUserRolesRequest',
        'StoreRoleRequest',
        'UpdateRoleRequest',
        'AssignRolePermissionsRequest',
    ])
        ->and($schemas)->not->toHaveKey('AdminRequest')
        ->and($schemas['StoreUserRequest']['required'])->toContain('name', 'email', 'password')
        ->and($schemas['LoginRequest']['required'])->toContain('email', 'password')
        ->and($schemas['AssignUserRolesRequest']['required'])->toContain('roles')
        ->and($document['paths']['/v1/auth/login']['post']['requestBody']['content']['application/json']['schema']['$ref'])
        ->toBe('#/components/schemas/LoginRequest')
        ->and($document['paths']['/v1/auth/login']['post']['responses']['422']['content']['application/problem+json']['schema']['required'])
        ->toContain('type', 'title', 'status', 'detail', 'instance', 'request_id', 'errors');

    foreach ([
        ['/v1/auth/logout', 'post'],
        ['/v1/auth/logout-all', 'post'],
        ['/v1/users/{user}', 'delete'],
        ['/v1/roles/{role}', 'delete'],
    ] as [$path, $method]) {
        expect($document['paths'][$path][$method]['responses']['204'])->not->toHaveKey('content');
    }
});

it('documents every application API route and Query Builder allowlist', function (): void {
    $document = exportedOpenApi();

    $documented = collect($document['paths'])->flatMap(
        fn (array $operations, string $path) => collect(array_keys($operations))
            ->map(fn (string $method): string => strtoupper($method).' '.$path),
    )->values();

    $routes = collect(Route::getRoutes())->filter(
        fn ($route): bool => str_starts_with($route->uri(), 'api/'),
    )->flatMap(
        fn ($route) => collect($route->methods())
            ->reject(fn (string $method): bool => $method === 'HEAD')
            ->reject(fn (string $method): bool => $method === 'PATCH' && in_array('PUT', $route->methods(), true))
            ->map(fn (string $method): string => $method.' /'.substr($route->uri(), 4)),
    )->unique()->values();

    expect($documented->sort()->values()->all())->toBe($routes->sort()->values()->all());

    $parameters = collect($document['paths']['/v1/users']['get']['parameters'])->keyBy('name');
    expect($parameters)->toHaveKeys([
        'filter[name]', 'filter[email]', 'filter[status]', 'sort', 'include', 'fields', 'page', 'per_page',
    ])
        ->and($parameters['sort']['schema']['enum'])->toContain('created_at', '-created_at')
        ->and($parameters['include']['schema']['enum'])->toBe(['roles']);
});
