<?php

namespace App\Providers;

use App\Http\Middleware\SetAdminLocaleFromDefaultLanguage;
use App\Support\Dates\DisplayDate;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        Livewire::addPersistentMiddleware([
            SetAdminLocaleFromDefaultLanguage::class,
        ]);

        FilamentTimezone::set(DisplayDate::timezone());
    }
}
