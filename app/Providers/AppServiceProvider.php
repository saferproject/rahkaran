<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        RateLimiter::for('backend-token', function (Request $request): Limit {
            return Limit::perMinute(10)->by(
                $request->ip().'|'.$request->string('client_id')->toString()
            );
        });

        RateLimiter::for('backend-api', function (Request $request): Limit {
            return Limit::perMinute(120)->by(
                (string) ($request->user()?->getKey() ?? $request->ip())
            );
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
