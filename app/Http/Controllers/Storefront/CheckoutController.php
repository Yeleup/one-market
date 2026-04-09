<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Orders\PlaceClientOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Checkout\PlaceOrderRequest;
use App\Models\Client;
use App\Models\Institution;
use App\Support\Storefront\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function index(Cart $cart): Response
    {
        /** @var Client $client */
        $client = Auth::guard('client')->user();

        $detailed = $cart->getDetailed();

        return Inertia::render('Storefront/Checkout', [
            'items' => $detailed->map(fn (array $item) => [
                'product_id' => $item['product']->id,
                'name' => $item['product']->localized_name,
                'bonus_price' => $item['product']->bonus_price,
                'quantity' => $item['quantity'],
                'line_total_bonus' => $item['line_total_bonus'],
                'line_total_weight_grams' => $item['line_total_weight_grams'],
            ]),
            'totalBonuses' => $detailed->sum('line_total_bonus'),
            'totalWeightGrams' => $detailed->sum('line_total_weight_grams'),
            'availableBonuses' => $client->bonus_balance - $client->bonus_reserved,
            'defaultInstitutionId' => $client->institution_id,
            'institutions' => Institution::query()
                ->where('is_active', true)
                ->withLocalizedName()
                ->get()
                ->map(fn (Institution $inst) => [
                    'id' => $inst->id,
                    'name' => $inst->localized_name,
                    'max_weight_grams' => $inst->max_weight_grams,
                ]),
        ]);
    }

    public function store(PlaceOrderRequest $request, Cart $cart, PlaceClientOrderAction $action): RedirectResponse
    {
        /** @var Client $client */
        $client = Auth::guard('client')->user();

        $order = $action->handle($client, $cart, $request->validated('institution_id'));

        return redirect()
            ->route('storefront.orders.show', $order)
            ->with('success', 'Заказ успешно оформлен.');
    }
}
