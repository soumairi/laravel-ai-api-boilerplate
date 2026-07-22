<?php

declare(strict_types=1);

use App\Application\Administration\SuperAdminGuard;
use App\Application\Auth\TokenService;
use App\Domain\Identity\LastSuperAdminViolation;
use App\Domain\Identity\UserStatus;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

it('does not seed role-management permissions for administrators', function (): void {
    $admin = Role::findByName('admin', 'api');

    expect($admin->hasPermissionTo('users.assignRoles'))->toBeTrue()
        ->and($admin->hasPermissionTo('roles.create'))->toBeFalse()
        ->and($admin->hasPermissionTo('roles.update'))->toBeFalse()
        ->and($admin->hasPermissionTo('roles.assignPermissions'))->toBeFalse()
        ->and(config('rbac.permissions'))->not->toContain(
            'users.restore',
            'permissions.assign',
            'audit.viewAny',
            'health.view',
        );
});

it('prevents an administrator from assigning protected roles', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('admin');
    $subject = User::factory()->create();
    $headers = $this->bearer(app(TokenService::class)->issueAccessToken($actor));

    $this->assertProblem(
        $this->withHeaders($headers)->putJson("/api/v1/users/{$subject->id}/roles", [
            'roles' => ['super-admin'],
        ]),
        403,
    );

    $this->assertProblem(
        $this->withHeaders($headers)->postJson('/api/v1/users', [
            'name' => 'Escalated User',
            'email' => 'escalated@example.test',
            'password' => 'V3ry-Str0ng!Passphrase',
            'password_confirmation' => 'V3ry-Str0ng!Passphrase',
            'roles' => ['admin'],
        ]),
        403,
    );

    expect($subject->refresh()->roles)->toHaveCount(0);
    $this->assertDatabaseMissing('users', ['email' => 'escalated@example.test']);
});

it('prevents an administrator from taking over a protected account', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('admin');
    $subject = User::factory()->create();
    $subject->assignRole('super-admin');
    $headers = $this->bearer(app(TokenService::class)->issueAccessToken($actor));

    $this->assertProblem(
        $this->withHeaders($headers)->putJson("/api/v1/users/{$subject->id}", [
            'email' => 'takeover@example.test',
            'password' => 'An0ther-Str0ng!Passphrase',
            'password_confirmation' => 'An0ther-Str0ng!Passphrase',
        ]),
        403,
    );
    $this->assertProblem(
        $this->withHeaders($headers)->patchJson("/api/v1/users/{$subject->id}/status", [
            'status' => UserStatus::Suspended->value,
        ]),
        403,
    );
    $this->assertProblem(
        $this->withHeaders($headers)->putJson("/api/v1/users/{$subject->id}/roles", [
            'roles' => ['user'],
        ]),
        403,
    );

    expect($subject->refresh()->email)->not->toBe('takeover@example.test')
        ->and($subject->status)->toBe(UserStatus::Active)
        ->and($subject->hasRole('super-admin'))->toBeTrue();
});

it('keeps every seeded system role immutable through the API', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('super-admin');
    $headers = $this->bearer(app(TokenService::class)->issueAccessToken($actor));

    foreach ((array) config('rbac.system_roles') as $roleName) {
        $role = Role::findByName($roleName, 'api');

        $this->assertProblem(
            $this->withHeaders($headers)->putJson("/api/v1/roles/{$role->id}", [
                'name' => "{$roleName}-renamed",
            ]),
            403,
        );
        $this->assertProblem(
            $this->withHeaders($headers)->putJson("/api/v1/roles/{$role->id}/permissions", [
                'permissions' => ['users.viewAny'],
            ]),
            403,
        );
        $this->assertProblem(
            $this->withHeaders($headers)->deleteJson("/api/v1/roles/{$role->id}"),
            409,
        );
    }
});

it('preserves at least one active super administrator', function (): void {
    $active = User::factory()->create();
    $active->assignRole('super-admin');
    $suspended = User::factory()->create(['status' => UserStatus::Suspended]);
    $suspended->assignRole('super-admin');

    expect(fn () => DB::transaction(
        fn () => app(SuperAdminGuard::class)->assertCanDeactivate($active, UserStatus::Suspended),
    ))->toThrow(LastSuperAdminViolation::class)
        ->and(fn () => DB::transaction(
            fn () => app(SuperAdminGuard::class)->assertCanRemoveRoleOrDelete($active),
        ))->toThrow(LastSuperAdminViolation::class);
});

it('revokes all sessions when an administrator changes credentials', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('super-admin');
    $subject = User::factory()->create(['password' => 'password']);

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => $subject->email,
        'password' => 'password',
    ]);
    $this->assertApiSuccess($login);
    $accessToken = (string) $login->json('data.access_token');
    $refreshToken = (string) $login->json('data.refresh_token');

    $this->assertApiSuccess(
        $this->withHeaders($this->bearer(app(TokenService::class)->issueAccessToken($actor)))
            ->putJson("/api/v1/users/{$subject->id}", [
                'password' => 'An0ther-Str0ng!Passphrase',
                'password_confirmation' => 'An0ther-Str0ng!Passphrase',
            ]),
    );

    $this->assertProblem(
        $this->withHeaders($this->bearer($accessToken))->getJson('/api/v1/auth/me'),
        401,
    );
    $this->assertProblem(
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $refreshToken]),
        401,
    );

    expect($subject->refresh()->token_version)->toBe(2)
        ->and($subject->refreshSessions()->whereNull('revoked_at')->exists())->toBeFalse();
});

it('rejects unknown administration fields and invalid pagination', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('super-admin');
    $subject = User::factory()->create();
    $headers = $this->bearer(app(TokenService::class)->issueAccessToken($actor));

    $this->assertProblem(
        $this->withHeaders($headers)->putJson("/api/v1/users/{$subject->id}", [
            'name' => 'Allowed Name',
            'token_version' => 999,
        ]),
        422,
    )->assertJsonStructure(['errors' => ['token_version']]);

    foreach (['0', '-1', 'invalid', '101'] as $perPage) {
        $this->assertProblem(
            $this->withHeaders($headers)->getJson("/api/v1/users?per_page=$perPage"),
            422,
        )->assertJsonStructure(['errors' => ['per_page']]);
    }
});

it('revokes every session before soft deleting an account', function (): void {
    $actor = User::factory()->create();
    $actor->assignRole('super-admin');
    $subject = User::factory()->create(['password' => 'password']);
    $login = $this->postJson('/api/v1/auth/login', [
        'email' => $subject->email,
        'password' => 'password',
    ]);
    $this->assertApiSuccess($login);

    $this->withHeaders($this->bearer(app(TokenService::class)->issueAccessToken($actor)))
        ->deleteJson("/api/v1/users/{$subject->id}")
        ->assertNoContent();

    $this->assertProblem(
        $this->withHeaders($this->bearer((string) $login->json('data.access_token')))
            ->getJson('/api/v1/auth/me'),
        401,
    );
    $this->assertProblem(
        $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $login->json('data.refresh_token'),
        ]),
        401,
    );

    $deleted = User::withTrashed()->findOrFail($subject->id);
    expect($deleted->trashed())->toBeTrue()
        ->and($deleted->refreshSessions()->whereNull('revoked_at')->exists())->toBeFalse();
});
