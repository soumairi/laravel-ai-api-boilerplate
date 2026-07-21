<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        Gate::policy(User::class, UserPolicy::class);
        Gate::before(static fn (User $user, string $ability): ?bool => $user->hasRole('super-admin') && str_contains($ability, '.')
            ? true
            : null);
        Horizon::auth(static fn (Request $request): bool => $request->user()?->can('horizon.view') === true);

        RateLimiter::for('auth', static fn (Request $request) => Limit::perMinute(
            (int) config('api.rate_limits.auth_per_minute'),
        )->by($request->ip()));
        RateLimiter::for('login', static fn (Request $request) => Limit::perMinute(
            (int) config('api.rate_limits.login_identity_per_minute'),
        )->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('register', static fn (Request $request) => Limit::perMinute(
            (int) config('api.rate_limits.register_per_minute'),
        )->by($request->ip()));
        RateLimiter::for('forgot-password', static fn (Request $request) => Limit::perMinutes(
            (int) config('api.rate_limits.forgot_password_decay_minutes'),
            (int) config('api.rate_limits.forgot_password_attempts'),
        )->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('refresh', static fn (Request $request) => Limit::perMinute(
            (int) config('api.rate_limits.refresh_per_minute'),
        )->by($request->ip()));
        RateLimiter::for('public-api', static fn (Request $request) => Limit::perMinute(
            (int) config('api.rate_limits.public_per_minute'),
        )->by($request->ip()));
        RateLimiter::for('api', static fn (Request $request) => Limit::perMinute(
            (int) config('api.rate_limits.authenticated_per_minute'),
        )->by($request->user()?->getAuthIdentifier() ?? $request->ip()));
        RateLimiter::for('administration', static fn (Request $request) => Limit::perMinute(
            (int) config('api.rate_limits.administration_per_minute'),
        )->by($request->user()?->getAuthIdentifier() ?? $request->ip()));
    }
}
