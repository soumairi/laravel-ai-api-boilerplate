<?php

declare(strict_types=1);

use App\Application\Auth\TokenService;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

it('seeds system roles and permissions idempotently', function (): void {
    $permissionCount = count(config('rbac.permissions'));

    $this->seed(DatabaseSeeder::class);

    expect(Role::query()->pluck('name')->all())->toEqualCanonicalizing(['super-admin', 'admin', 'user'])
        ->and(Role::findByName('user', 'api')->permissions)->toHaveCount(0)
        ->and(Permission::query()->count())->toBe($permissionCount)
        ->and(Permission::findByName('api.documentation.view', 'api'))->not->toBeNull()
        ->and(Permission::findByName('horizon.view', 'api'))->not->toBeNull();
});

it('seeds the configured administrator idempotently', function (): void {
    config(['rbac.admin' => [
        'name' => 'Bootstrap Administrator',
        'email' => 'bootstrap-admin@example.test',
        'password' => 'V3ry-Str0ng!Bootstrap',
    ]]);

    $this->seed(AdminUserSeeder::class);
    $seeded = User::query()->where('email', 'bootstrap-admin@example.test')->sole();
    $identifier = $seeded->id;
    $passwordHash = $seeded->password;
    $this->seed(AdminUserSeeder::class);
    $admin = User::query()->where('email', 'bootstrap-admin@example.test')->sole();

    expect($admin->id)->toBe($identifier)
        ->and($admin->password)->toBe($passwordHash)
        ->and($admin->hasRole('super-admin'))->toBeTrue()
        ->and(Hash::check('V3ry-Str0ng!Bootstrap', $admin->password))->toBeTrue();
});

it('refuses to seed an administrator without an explicit password', function (): void {
    config(['rbac.admin' => [
        'name' => 'Unsafe Administrator',
        'email' => 'unsafe-admin@example.test',
        'password' => null,
    ]]);

    expect(fn () => $this->seed(AdminUserSeeder::class))->toThrow(RuntimeException::class);
    $this->assertDatabaseMissing('users', ['email' => 'unsafe-admin@example.test']);
});

it('prevents an unprivileged user from listing administration resources', function (): void {
    $actor = User::factory()->create();
    $token = app(TokenService::class)->issueAccessToken($actor);

    $this->assertProblem(
        $this->withHeaders($this->bearer($token))->getJson('/api/v1/users'),
        403,
    );
});

it('supports only allowlisted query builder filters sorts includes and fields', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('super-admin');
    User::factory()->create(['name' => 'Filtered Person', 'email' => 'filtered@example.test']);
    $token = app(TokenService::class)->issueAccessToken($actor);

    $response = $this->withHeaders($this->bearer($token))
        ->getJson('/api/v1/users?filter[name]=Filtered&sort=name&include=roles&fields[users]=id,name,email,status');

    $this->assertApiSuccess($response)
        ->assertJsonFragment(['email' => 'filtered@example.test'])
        ->assertJsonStructure(['meta' => ['current_page', 'last_page', 'per_page', 'total']]);

    $this->withHeaders($this->bearer($token))
        ->getJson('/api/v1/users?filter[password]=secret')
        ->assertStatus(400);
});

it('prevents self privilege and status changes', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('super-admin');
    $token = app(TokenService::class)->issueAccessToken($actor);

    $this->assertProblem(
        $this->withHeaders($this->bearer($token))
            ->patchJson("/api/v1/users/{$actor->id}/status", ['status' => 'suspended']),
        403,
    );
});

it('protects the final super admin and immutable system RBAC resources', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('super-admin');
    $token = app(TokenService::class)->issueAccessToken($actor);
    $headers = $this->bearer($token);

    $this->assertProblem(
        $this->withHeaders($headers)->deleteJson("/api/v1/users/{$actor->id}"),
        403,
    );

    $role = Role::findByName('super-admin', 'api');
    $this->assertProblem(
        $this->withHeaders($headers)->deleteJson("/api/v1/roles/{$role->id}"),
        409,
    );
});

it('records auditable administration changes without credentials', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('super-admin');
    $token = app(TokenService::class)->issueAccessToken($actor);

    $response = $this->withHeaders($this->bearer($token))->postJson('/api/v1/users', [
        'name' => 'Audited User',
        'email' => 'audited@example.test',
        'password' => 'V3ry-Str0ng!Passphrase',
        'password_confirmation' => 'V3ry-Str0ng!Passphrase',
        'roles' => [],
    ]);

    $this->assertApiSuccess($response, 201);
    $activity = DB::table('activity_log')->where('event', 'created')->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and((string) $activity->properties)->not->toContain('password')
        ->not->toContain('V3ry-Str0ng!Passphrase');
});

it('manages a user status and roles through dedicated authorized endpoints', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('super-admin');
    $subject = User::factory()->create();
    $token = app(TokenService::class)->issueAccessToken($actor);
    $headers = $this->bearer($token);

    $this->assertApiSuccess($this->withHeaders($headers)->getJson("/api/v1/users/{$subject->id}"));
    $this->assertApiSuccess($this->withHeaders($headers)->putJson("/api/v1/users/{$subject->id}", [
        'name' => 'Updated User',
    ]))->assertJsonPath('data.name', 'Updated User');
    $this->assertApiSuccess($this->withHeaders($headers)->putJson("/api/v1/users/{$subject->id}/roles", [
        'roles' => ['admin'],
    ]));
    $this->assertApiSuccess($this->withHeaders($headers)->patchJson("/api/v1/users/{$subject->id}/status", [
        'status' => 'suspended',
    ]))->assertJsonPath('data.status', 'suspended');

    expect($subject->refresh()->hasRole('admin'))->toBeTrue()
        ->and($subject->token_version)->toBe(2);
    $this->withHeaders($headers)->deleteJson("/api/v1/users/{$subject->id}")->assertNoContent();
});

it('manages roles and their permissions while protecting system roles', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('super-admin');
    $headers = $this->bearer(app(TokenService::class)->issueAccessToken($actor));

    $created = $this->withHeaders($headers)->postJson('/api/v1/roles', [
        'name' => 'support',
        'permissions' => ['users.viewAny'],
    ]);
    $this->assertApiSuccess($created, 201);
    $roleId = $created->json('data.id');

    $this->assertApiSuccess($this->withHeaders($headers)->getJson('/api/v1/roles'));
    $this->assertApiSuccess($this->withHeaders($headers)->getJson("/api/v1/roles/{$roleId}"));
    $this->assertApiSuccess($this->withHeaders($headers)->putJson("/api/v1/roles/{$roleId}", [
        'name' => 'customer-support',
    ]));
    $this->assertApiSuccess($this->withHeaders($headers)->putJson("/api/v1/roles/{$roleId}/permissions", [
        'permissions' => ['users.viewAny', 'users.view'],
    ]));
    $this->withHeaders($headers)->deleteJson("/api/v1/roles/{$roleId}")->assertNoContent();
});

it('lists permissions for authorized administrators', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('super-admin');

    $response = $this->withHeaders($this->bearer(app(TokenService::class)->issueAccessToken($actor)))
        ->getJson('/api/v1/permissions?filter[name]=documentation&sort=name');

    $this->assertApiSuccess($response)
        ->assertJsonFragment(['name' => 'api.documentation.view']);
});
