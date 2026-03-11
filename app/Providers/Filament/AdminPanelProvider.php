<?php

namespace App\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/app.css')
            ->renderHook('panels::head.start',
                fn(): string => Vite::useHotFile('admin.hot')
                    ->useBuildDirectory('build/admin')
                    ->withEntryPoints(['resources/js/filament/admin/app.js',])->toHtml())
            ->login()
            ->registration()
            ->passwordReset()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    // layout customization
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 4,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ])
//                    ->simpleResourcePermissionView()
//                    ->localizePermissionLabels()
                // global search
//                    ->globallySearchable(true)                  // bool|Closure
//                    ->globalSearchResultsLimit(50)              // int|Closure
//                    ->forceGlobalSearchCaseInsensitive(true)    // bool|Closure|null
//                    ->splitGlobalSearchTerms(false)
                // label
//                    ->modelLabel('Model')                       // string|Closure|null
//                    ->pluralModelLabel('Models')                // string|Closure|null
//                    ->recordTitleAttribute('name')              // string|Closure|null
//                    ->titleCaseModelLabel(false)
                // navigation
//                    ->navigationLabel('Label')                  // string|Closure|null
//                    ->navigationIcon('heroicon-o-home')         // string|Closure|null
//                    ->activeNavigationIcon('heroicon-s-home')   // string|Closure|null
//                    ->navigationGroup('Group')                  // string|Closure|null
//                    ->navigationSort(10)                        // int|Closure|null
//                    ->navigationBadge('5')                      // string|Closure|null
//                    ->navigationBadgeColor('success')           // string|array|Closure|null
//                    ->navigationParentItem('parent.item')       // string|Closure|null
//                    ->registerNavigation(true)                     // bool|Closure,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s');
    }
}
