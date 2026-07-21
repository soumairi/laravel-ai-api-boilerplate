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
use App\Http\Requests\AuthRequest;
use App\Http\Resources\UserResource;
use App\Infrastructure\Http\ApiResponse;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function register(AuthRequest $request): JsonResponse
    {
        abort_unless(config('api.registration_enabled'), 404);
        $user = User::query()->create([
            ...$request->safe()->only(['name', 'email', 'password']),
            'status' => config('api.require_email_verification') ? UserStatus::Pending : UserStatus::Active,
        ]);
        $user->assignRole((string) config('rbac.default_role'));

        if (config('api.require_email_verification')) {
            $user->sendEmailVerificationNotification();
        } else {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        activity('authentication')->performedOn($user)->event('registered')->log('User registered');

        return ApiResponse::success(new UserResource($user), 201);
    }

    public function login(AuthRequest $request): JsonResponse
    {
        $user = User::query()->where('email', Str::lower($request->string('email')->toString()))->first();
        abort_unless($user && Hash::check($request->string('password')->toString(), $user->password), 401, 'Invalid credentials.');
        abort_unless($user->status->mayAuthenticate(), 403, 'Account is not active.');

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        activity('authentication')->causedBy($user)->performedOn($user)->event('login')->log('User logged in');

        return $this->tokenResponse($user, $this->issueTokenPair->execute($user, $this->clientContext($request)));
    }

    public function refresh(AuthRequest $request): JsonResponse
    {
        $rotated = $this->rotateTokenPair->execute(
            TokenIdentifier::fromString($request->string('refresh_token')->toString()),
            $this->clientContext($request),
        );
        abort_unless($rotated->user->status->mayAuthenticate(), 403, 'Account is not active.');

        return $this->tokenResponse($rotated->user, $rotated->tokenPair);
    }

    public function logout(AuthRequest $request): JsonResponse
    {
        $this->refreshTokens->revoke(TokenIdentifier::fromString($request->string('refresh_token')->toString()));
        $this->tokens->invalidateCurrent();
        activity('authentication')->causedBy($request->user())->event('logout')->log('User logged out');

        return ApiResponse::success(null, 204);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->refreshTokens->revokeAll($request->user(), 'logout_all');
        activity('authentication')->causedBy($request->user())->event('logout_all')->log('All sessions revoked');

        return ApiResponse::success(null, 204);
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()->load('roles', 'permissions')));
    }

    public function forgotPassword(AuthRequest $request): JsonResponse
    {
        Password::sendResetLink($request->safe()->only('email'));

        return ApiResponse::success(['message' => 'If the account exists, reset instructions were queued.'], 202);
    }

    public function resetPassword(AuthRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->safe()->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password, 'token_version' => $user->token_version + 1]);
                $user->save();
                $user->refreshSessions()->whereNull('revoked_at')->update(['revoked_at' => now(), 'revoke_reason' => 'password_reset']);
                event(new PasswordReset($user));
            },
        );

        abort_unless($status === Password::PasswordReset, 422, __($status));

        return ApiResponse::success(['message' => 'Password reset completed.']);
    }

    public function changePassword(AuthRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->forceFill(['password' => $request->string('password')->toString()])->save();
        $this->refreshTokens->revokeAll($user, 'password_changed');
        activity('authentication')->causedBy($user)->performedOn($user)->event('password_changed')->log('Password changed');

        return ApiResponse::success(['message' => 'Password changed.']);
    }

    public function verify(Request $request, string $id, string $hash): JsonResponse
    {
        $user = User::query()->findOrFail($id);
        abort_unless($request->hasValidSignature() && hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $user->forceFill(['status' => UserStatus::Active])->save();
        }

        return ApiResponse::success(['message' => 'Email verified.']);
    }

    public function resendVerification(AuthRequest $request): JsonResponse
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
