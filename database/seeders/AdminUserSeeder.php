<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Identity\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

final class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) config('rbac.admin.email'));
        if ($email === '') {
            return;
        }

        $password = (string) config('rbac.admin.password');
        if ($password === '') {
            throw new RuntimeException('ADMIN_PASSWORD is required when ADMIN_EMAIL is set; no administrator was seeded.');
        }

        $user = User::query()->firstOrNew(['email' => Str::lower($email)]);
        $user->name = (string) config('rbac.admin.name');
        $user->status = UserStatus::Active;
        $user->email_verified_at ??= now();
        if (! $user->exists || ! Hash::check($password, $user->password)) {
            $user->password = $password;
        }
        $user->save();

        $user->syncRoles([Role::findByName('super-admin', 'api')]);
    }
}
