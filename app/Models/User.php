<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Identity\UserStatus;
use App\Infrastructure\Notifications\QueuedResetPassword;
use App\Infrastructure\Notifications\QueuedVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property UserStatus $status
 * @property int $token_version
 */
#[Fillable(['name', 'first_name', 'last_name', 'email', 'password', 'status'])]
#[Hidden(['password', 'remember_token', 'token_version'])]
class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasUlids, Notifiable, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    public function uniqueIds(): array
    {
        return ['id'];
    }

    /** @return HasMany<RefreshSession, $this> */
    public function refreshSessions(): HasMany
    {
        return $this->hasMany(RefreshSession::class);
    }

    public function getJWTIdentifier(): string
    {
        return (string) $this->getKey();
    }

    /** @return array<string, int|string> */
    public function getJWTCustomClaims(): array
    {
        return ['ver' => $this->token_version, 'status' => $this->status->value];
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new QueuedVerifyEmail);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new QueuedResetPassword((string) $token));
    }

    protected function email(): Attribute
    {
        return Attribute::make(set: static fn (string $value): string => strtolower(trim($value)));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'token_version' => 'integer',
            'last_login_at' => 'datetime',
        ];
    }
}
