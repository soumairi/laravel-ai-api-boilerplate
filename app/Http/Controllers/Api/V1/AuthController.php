<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Auth\Actions\IssueTokenPairAction;
use App\Application\Auth\Actions\RotateTokenPairAction;
use App\Application\Auth\ClientContext;
use App\Application\Auth\RefreshTokenService;
use App\Application\Auth\TokenPair;
use App\Application\Auth\TokenService;
use App\Domain\Auth\TokenIdentifier;
use App\Domain\Identity\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\EmailRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Infrastructure\Http\ApiResponse;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final class AuthController extends Controller
{
    public function __construct(
        private readonly TokenService $tokens,
        private readonly RefreshTokenService $refreshTokens,
        private readonly IssueTokenPairAction $issueTokenPair,
        private readonly RotateTokenPairAction $rotateTokenPair,
    ) {}

    public function registrationConfig(): JsonResponse
    {
        return ApiResponse::success([
            'enabled' => config('api.registration_enabled'),
            'require_email_verification' => config('api.require_email_verification'),
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        abort_unless(config('api.registration_enabled'), 404);
        $user = DB::transaction(function () use ($request): User {
            $user = User::query()->create([
                ...$request->safe()->only(['name', 'email', 'password']),
                'status' => config('api.require_email_verification') ? UserStatus::Pending : UserStatus::Active,
            ]);
            $user->assignRole((string) config('rbac.default_role'));
            if (! config('api.require_email_verification')) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            return $user;
        });

        if (config('api.require_email_verification')) {
            $user->sendEmailVerificationNotification();
        }

        activity('authentication')->performedOn($user)->event('registered')->log('User registered');

        return ApiResponse::success(new UserResource($user), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', Str::lower($request->string('email')->toString()))->first();
        abort_unless($user && Hash::check($request->string('password')->toString(), $user->password), 401, 'Invalid credentials.');
        abort_unless($user->status->mayAuthenticate(), 403, 'Account is not active.');

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        activity('authentication')->causedBy($user)->performedOn($user)->event('login')->log('User logged in');

        return $this->tokenResponse($user, $this->issueTokenPair->execute($user, $this->clientContext($request)));
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $rotated = $this->rotateTokenPair->execute(
            TokenIdentifier::fromString($request->string('refresh_token')->toString()),
            $this->clientContext($request),
        );
        abort_unless($rotated->user->status->mayAuthenticate(), 403, 'Account is not active.');

        return $this->tokenResponse($rotated->user, $rotated->tokenPair);
    }

    public function logout(RefreshTokenRequest $request): Response
    {
        $this->refreshTokens->revoke(TokenIdentifier::fromString($request->string('refresh_token')->toString()));
        $this->tokens->invalidateCurrent();
        activity('authentication')->causedBy($request->user())->event('logout')->log('User logged out');

        return response()->noContent();
    }

    public function logoutAll(Request $request): Response
    {
        $this->refreshTokens->revokeAll($request->user(), 'logout_all');
        activity('authentication')->causedBy($request->user())->event('logout_all')->log('All sessions revoked');

        return response()->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()->load('roles', 'permissions')));
    }

    public function forgotPassword(EmailRequest $request): JsonResponse
    {
        Password::sendResetLink($request->safe()->only('email'));

        return ApiResponse::success(['message' => 'If the account exists, reset instructions were queued.'], 202);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->safe()->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user = DB::transaction(function () use ($user, $password): User {
                    $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
                    $lockedUser->forceFill(['password' => $password])->save();
                    $this->refreshTokens->revokeAll($lockedUser, 'password_reset');

                    return $lockedUser;
                });
                event(new PasswordReset($user));
            },
        );

        abort_unless($status === Password::PasswordReset, 422, __($status));

        return ApiResponse::success(['message' => 'Password reset completed.']);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        DB::transaction(function () use ($request, $user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $lockedUser->forceFill(['password' => $request->string('password')->toString()])->save();
            $this->refreshTokens->revokeAll($lockedUser, 'password_changed');
        });
        activity('authentication')->causedBy($user)->performedOn($user)->event('password_changed')->log('Password changed');

        return ApiResponse::success(['message' => 'Password changed.']);
    }

    public function verify(Request $request, string $id, string $hash): JsonResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        DB::transaction(function () use ($id, $hash): void {
            $user = User::query()->lockForUpdate()->findOrFail($id);
            abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
                if ($user->status === UserStatus::Pending) {
                    $user->forceFill(['status' => UserStatus::Active])->save();
                }
            }
        });

        return ApiResponse::success(['message' => 'Email verified.']);
    }

    public function resendVerification(EmailRequest $request): JsonResponse
    {
        $user = User::query()->where('email', Str::lower($request->string('email')->toString()))->first();
        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return ApiResponse::success(['message' => 'Verification notification queued.'], 202);
    }

    private function tokenResponse(User $user, TokenPair $tokenPair): JsonResponse
    {
        return ApiResponse::success([
            'token_type' => 'Bearer',
            'access_token' => $tokenPair->accessToken,
            'expires_in' => $tokenPair->accessExpiresIn,
            'refresh_token' => $tokenPair->refreshToken,
            'refresh_expires_at' => $tokenPair->refreshExpiresAt,
            'user' => new UserResource($user),
        ]);
    }

    private function clientContext(Request $request): ClientContext
    {
        return new ClientContext($request->ip(), $request->userAgent());
    }
}
