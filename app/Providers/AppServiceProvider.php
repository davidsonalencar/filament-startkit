<?php

namespace App\Providers;

use BezhanSalleh\FilamentShield\Commands;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
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
        $this->registerProhibitedCommands();
    }

    protected function registerProhibitedCommands(): void
    {
        // individually prohibit commands
        Commands\GenerateCommand::prohibit($this->app->isProduction());
        Commands\InstallCommand::prohibit($this->app->isProduction());
        Commands\PublishCommand::prohibit($this->app->isProduction());
        Commands\SetupCommand::prohibit($this->app->isProduction());
        Commands\SeederCommand::prohibit($this->app->isProduction());
        Commands\SuperAdminCommand::prohibit($this->app->isProduction());
        // or prohibit the above commands all at once
        FilamentShield::prohibitDestructiveCommands($this->app->isProduction());
    }
}
