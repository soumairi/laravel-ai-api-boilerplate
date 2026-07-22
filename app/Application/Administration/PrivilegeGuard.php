<?php

declare(strict_types=1);

namespace App\Application\Administration;

use App\Models\User;
use Spatie\Permission\Models\Role;

final class PrivilegeGuard
{
    public function canManageUser(User $actor, User $subject): bool
    {
        if ($actor->is($subject)) {
            return false;
        }

        return $actor->hasRole('super-admin')
            || ! $subject->hasAnyRole((array) config('rbac.protected_roles', []));
    }

    /** @param array<int, string> $roleNames */
    public function canAssignRoles(User $actor, array $roleNames): bool
    {
        if ($actor->hasRole('super-admin')) {
            return true;
        }

        if (array_intersect($roleNames, (array) config('rbac.protected_roles', [])) !== []) {
            return false;
        }

        $roles = Role::query()
            ->where('guard_name', 'api')
            ->whereIn('name', $roleNames)
            ->with('permissions')
            ->get();

        if ($roles->count() !== count(array_unique($roleNames))) {
            return false;
        }

        $actorPermissions = $actor->getAllPermissions()->pluck('name');

        return $roles->flatMap->permissions->pluck('name')->diff($actorPermissions)->isEmpty();
    }

    public function canManageRole(User $actor, Role $role): bool
    {
        return ! $this->isSystemRole($role) && $actor->hasRole('super-admin');
    }

    /** @param array<int, string> $permissionNames */
    public function canAssignPermissions(User $actor, Role $role, array $permissionNames): bool
    {
        if (! $this->canManageRole($actor, $role)) {
            return false;
        }

        return collect($permissionNames)->diff(config('rbac.permissions'))->isEmpty();
    }

    public function isSystemRole(Role $role): bool
    {
        return in_array($role->name, (array) config('rbac.system_roles', []), true);
    }
}
