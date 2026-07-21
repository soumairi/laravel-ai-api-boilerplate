<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

function exportedOpenApi(): array
{
    $path = base_path('docs/openapi/openapi.json');
    Artisan::call('scramble:export');

    expect(Artisan::output())->not->toContain('ERROR')
        ->and($path)->toBeFile();

    return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
}

it('renders the Scalar API reference outside production', function (): void {
    $this->get('/docs/api')
        ->assertOk()
        ->assertSee('@scalar/api-reference', escape: false);
});

it('protects documentation in production', function (): void {
    app()->detectEnvironment(static fn (): string => 'production');

    try {
        $this->get('/docs/api')->assertForbidden();
        $this->get('/docs/api.json')->assertForbidden();
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
        $this->actingAs($docsUser, 'api')->get('/docs/api')->assertOk();
        $this->actingAs($horizonUser, 'api')->get('/docs/api')->assertForbidden();
        $this->actingAs($horizonUser, 'api')->get('/horizon')->assertOk();
        $this->actingAs($docsUser, 'api')->get('/horizon')->assertForbidden();
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
        }
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
