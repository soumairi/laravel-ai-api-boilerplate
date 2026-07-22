<?php

declare(strict_types=1);

namespace App\Policies;

use App\Application\Administration\PrivilegeGuard;
use App\Models\User;

final class UserPolicy
{
    public function __construct(private readonly PrivilegeGuard $privileges) {}

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
        return $actor->can('users.update') && $this->privileges->canManageUser($actor, $subject);
    }

    public function delete(User $actor, User $subject): bool
    {
        return $actor->can('users.delete') && $this->privileges->canManageUser($actor, $subject);
    }

    public function changeStatus(User $actor, User $subject): bool
    {
        return $actor->can('users.changeStatus') && $this->privileges->canManageUser($actor, $subject);
    }

    public function assignRoles(User $actor, User $subject): bool
    {
        return $actor->can('users.assignRoles') && $this->privileges->canManageUser($actor, $subject);
    }
}
