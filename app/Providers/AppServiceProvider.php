<?php

namespace App\Providers;

use App\Support\FilamentDateFormats;
use BezhanSalleh\FilamentShield\Commands;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
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
        $this->configureDeleteBulkAction();
        $this->configureFormats();
    }

    protected function configureFormats(): void
    {
        \Illuminate\Support\Number::useLocale($this->app->getLocale());

        $dateTimeFormat = FilamentDateFormats::dateTime();
        $dateFormat = FilamentDateFormats::date();
        $timeFormat = FilamentDateFormats::time();

        Table::configureUsing(fn(Table $table) => $table
            ->defaultDateTimeDisplayFormat($dateTimeFormat)
            ->defaultDateDisplayFormat($dateFormat)
            ->defaultTimeDisplayFormat($timeFormat)
        );

        Schema::configureUsing(fn(Schema $schema) => $schema
            ->defaultDateTimeDisplayFormat($dateTimeFormat)
            ->defaultDateDisplayFormat($dateFormat)
            ->defaultTimeDisplayFormat($timeFormat)
        );

        DateTimePicker::configureUsing(fn(DateTimePicker $component) => $component->displayFormat($dateTimeFormat));
        DatePicker::configureUsing(fn(DatePicker $component) => $component->displayFormat($dateFormat));
        TimePicker::configureUsing(fn(TimePicker $component) => $component->displayFormat($timeFormat));

    }

    protected function configureDeleteBulkAction(): void
    {
        DeleteBulkAction::configureUsing(function (DeleteBulkAction $action) {
            $action->authorize('delete');
        });
    }

    protected function registerProhibitedCommands(): void
    {
        $isProduction = $this->app->isProduction();

        // individually prohibit commands
        Commands\GenerateCommand::prohibit($isProduction);
        Commands\InstallCommand::prohibit($isProduction);
        Commands\PublishCommand::prohibit($isProduction);
        Commands\SetupCommand::prohibit($isProduction);
        Commands\SeederCommand::prohibit($isProduction);
        Commands\SuperAdminCommand::prohibit($isProduction);
        // or prohibit the above commands all at once
        FilamentShield::prohibitDestructiveCommands($isProduction);
    }
}
