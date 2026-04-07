<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Number;
use Symfony\Component\HttpFoundation\Response;

class SetAdminLocaleFromDefaultLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Schema::hasTable('languages')) {
            App::setLocale(Language::resolveAdminLocale());
            Number::useLocale(App::currentLocale());
        }

        return $next($request);
    }
}
