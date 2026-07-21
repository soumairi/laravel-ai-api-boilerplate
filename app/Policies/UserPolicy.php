<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('users.viewAny');
    }

    public function view(User $actor, User $subject): bool
    {
        return $actor->can('users.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('users.create');
    }

    public function update(User $actor, User $subject): bool
    {
        return $actor->can('users.update');
    }

    public function delete(User $actor, User $subject): bool
    {
        if (! $actor->can('users.delete') || $actor->is($subject)) {
            return false;
        }

        $superAdmins = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'super-admin'))->count();

        return ! ($subject->hasRole('super-admin') && $superAdmins <= 1);
    }

    public function changeStatus(User $actor, User $subject): bool
    {
        return $actor->can('users.changeStatus') && ! $actor->is($subject);
    }

    public function assignRoles(User $actor, User $subject): bool
    {
        return $actor->can('users.assignRoles') && ! $actor->is($subject);
    }
}
