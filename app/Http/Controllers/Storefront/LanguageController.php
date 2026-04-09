<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Middleware\HandleStorefrontInertiaRequests;
use App\Http\Requests\Storefront\SetLanguageRequest;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;

class LanguageController extends Controller
{
    public function __invoke(SetLanguageRequest $request): RedirectResponse
    {
        $languageCode = $request->string('language')->toString();

        if ($languageCode === Language::resolveStorefrontLocale()) {
            $request->session()->forget(HandleStorefrontInertiaRequests::SESSION_KEY);
        } else {
            $request->session()->put(HandleStorefrontInertiaRequests::SESSION_KEY, $languageCode);
        }

        return redirect()->back();
    }
}
