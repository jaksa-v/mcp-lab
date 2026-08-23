<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Mcp\Client;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Passport\Passport;

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
        $this->configureDefaults();
        $this->configureRateLimiting();
        $this->configurePassport();
        $this->registerDirectoryClient();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Register named rate limiters used by HTTP routes.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('mcp', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * The MCP authorize screen is Blade from the package. Do not rewrite it in React.
     */
    protected function configurePassport(): void
    {
        Passport::authorizationView('mcp.authorize');
    }

    /**
     * Ticket tools look up people through this named client. Local stdio avoids
     * deadlocking php artisan serve, which can only handle one HTTP request.
     */
    protected function registerDirectoryClient(): void
    {
        Mcp::registerClient('directory', fn (): Client => Client::local('php', [
            'artisan',
            'mcp:start',
            'directory',
        ]));
    }
}
