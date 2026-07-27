<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

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
        ResetPassword::createUrlUsing(
            function (User $user, string $token): string {
                $frontendUrl = rtrim(
                    (string) config('app.frontend_url'),
                    '/'
                );

                return $frontendUrl
                    .'/reset-password?token='
                    .urlencode($token)
                    .'&email='
                    .urlencode(
                        $user->getEmailForPasswordReset()
                    );
            }
        );

        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower(
                (string) $request->input('email')
            );

            return Limit::perMinute(5)->by(
                $email.'|'.$request->ip()
            );
        });

        RateLimiter::for(
            'password-reset-link',
            function (Request $request) {
                $email = Str::lower(
                    (string) $request->input('email')
                );

                return Limit::perMinute(3)->by(
                    $email.'|'.$request->ip()
                );
            }
        );

        RateLimiter::for(
            'password-reset',
            function (Request $request) {
                $email = Str::lower(
                    (string) $request->input('email')
                );

                return Limit::perMinute(5)->by(
                    $email.'|'.$request->ip()
                );
            }
        );
    
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->isSuperAdmin()
                ? true
                : null;
        });
    }
}
