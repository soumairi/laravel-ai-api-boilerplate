<?php

declare(strict_types=1);

namespace App\Application\Administration;

use App\Domain\Identity\LastSuperAdminViolation;
use App\Domain\Identity\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class SuperAdminGuard
{
    private const int ACTIVE_SUPER_ADMIN_LOCK = 7_157_621_431_991_104_827;

    /** @param list<string>|null $newRoles */
    public function assertCanRemoveRoleOrDelete(User $subject, ?array $newRoles = null): void
    {
        $superAdmins = $this->lockSuperAdmins();
        $isSuperAdmin = $superAdmins->contains(
            static fn (User $user): bool => $user->is($subject),
        );
        $removesRole = $newRoles === null || ! in_array('super-admin', $newRoles, true);

        if (! $isSuperAdmin || ! $removesRole) {
            return;
        }

        if ($superAdmins->count() <= 1) {
            throw new LastSuperAdminViolation('The final super-admin cannot be removed.');
        }

        if ($subject->status === UserStatus::Active && $this->activeCount($superAdmins) <= 1) {
            throw new LastSuperAdminViolation('The final active super-admin cannot be removed.');
        }
    }

    public function assertCanDeactivate(User $subject, UserStatus $newStatus): void
    {
        $superAdmins = $this->lockSuperAdmins();
        $current = $superAdmins->first(
            static fn (User $user): bool => $user->is($subject),
        );

        if (! $current
            || $current->status !== UserStatus::Active
            || $newStatus->mayAuthenticate()) {
            return;
        }

        if ($this->activeCount($superAdmins) <= 1) {
            throw new LastSuperAdminViolation('The final active super-admin cannot be deactivated.');
        }
    }

    /** @return Collection<int, User> */
    private function lockSuperAdmins(): Collection
    {
        DB::select('SELECT pg_advisory_xact_lock(?)', [self::ACTIVE_SUPER_ADMIN_LOCK]);

        return User::query()
            ->whereHas('roles', static fn ($query) => $query
                ->where('name', 'super-admin')
                ->where('guard_name', 'api'))
            ->lockForUpdate()
            ->get();
    }

    /** @param Collection<int, User> $superAdmins */
    private function activeCount(Collection $superAdmins): int
    {
        return $superAdmins->filter(
            static fn (User $user): bool => $user->status === UserStatus::Active,
        )->count();
    }
}
