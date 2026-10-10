<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Passkeys;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::loginView(fn () => view('admin.login'));
        Fortify::twoFactorChallengeView(fn () => view('admin.challenge'));
        Fortify::confirmPasswordView(fn () => view('admin.confirm'));
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::where('email', strtolower($request->input('email', '')))->first();

            return $user?->is_admin && ! $user->password_setup_pending && Hash::check((string) $request->input('password'), $user->password) ? $user : null;
        });
        Passkeys::authorizeLoginUsing(function (Request $request, User $user): bool {
            if (! $user->is_admin || $user->password_setup_pending) {
                return false;
            }
            $request->session()->put('auth.password_confirmed_at', time());

            return true;
        });
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower($request->input('email', '')).'|'.$request->ip()));
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id').'|'.$request->ip()));
        RateLimiter::for('passkeys', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }
}
