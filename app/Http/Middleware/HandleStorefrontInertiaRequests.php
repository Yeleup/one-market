<?php

namespace App\Http\Middleware;

use App\Models\Language;
use App\Support\Storefront\Cart;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Number;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;

class HandleStorefrontInertiaRequests extends Middleware
{
    public const SESSION_KEY = 'storefront_locale';

    protected $rootView = 'app';

    public function handle(Request $request, Closure $next): Response
    {
        $this->setStorefrontLocale($request);

        return parent::handle($request, $next);
    }

    public function share(Request $request): array
    {
        $client = Auth::guard('client')->user();
        $cart = app(Cart::class);

        return [
            ...parent::share($request),
            'auth' => [
                'client' => $client ? [
                    'id' => $client->id,
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'full_name' => $client->full_name,
                    'bin' => $client->bin,
                    'bonus_balance' => $client->bonus_balance,
                    'bonus_reserved' => $client->bonus_reserved,
                    'available_bonuses' => $client->bonus_balance - $client->bonus_reserved,
                ] : null,
            ],
            'cart' => [
                'count' => $cart->count(),
                'total_quantity' => $cart->totalQuantity(),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'locale' => $this->sharedLocaleData(),
            'translations' => Lang::get('storefront'),
        ];
    }

    private function setStorefrontLocale(Request $request): void
    {
        if (! Schema::hasTable('languages')) {
            return;
        }

        $locale = Language::resolveStorefrontLocale(
            $request->session()->get(self::SESSION_KEY),
        );

        App::setLocale($locale);
        Number::useLocale($locale);
    }

    /**
     * @return array{
     *     current: string,
     *     available: array<int, array{code: string, name: string}>
     * }
     */
    private function sharedLocaleData(): array
    {
        if (! Schema::hasTable('languages')) {
            return [
                'current' => App::currentLocale(),
                'available' => [],
            ];
        }

        return [
            'current' => App::currentLocale(),
            'available' => Language::query()
                ->active()
                ->ordered()
                ->get(['code', 'name'])
                ->map(fn (Language $language): array => [
                    'code' => $language->code,
                    'name' => $language->name,
                ])
                ->values()
                ->all(),
        ];
    }
}
