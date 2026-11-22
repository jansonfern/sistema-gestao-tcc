<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Mail;
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
        Paginator::useBootstrapFive();
        $this->app['router']->aliasMiddleware('role', \App\Http\Middleware\CheckRole::class);

        $mailRedirect = config('mail.redirect_to');
        if ($mailRedirect) {
            Mail::alwaysTo(array_map('trim', explode(',', $mailRedirect)));
        }

        RateLimiter::for('custom', function (Request $request) {
            if ($request->user()?->isAdmin()) {
                return Limit::none();
            }

            if ($request->user()) {
                return Limit::perMinute(60)->by($request->user()->id);
            }

            return Limit::perMinute(20)->by($request->ip());
        });
    }
}
