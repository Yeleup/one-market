<?php

namespace App\Providers;

use App\Http\Middleware\SetAdminLocaleFromDefaultLanguage;
use App\Models\Language;
use Illuminate\Support\Number;
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
        app()->setLocale(Language::resolveAdminLocale(config('app.locale')));
        Number::useLocale(app()->getLocale());

        Livewire::addPersistentMiddleware([
            SetAdminLocaleFromDefaultLanguage::class,
        ]);
    }
}
