<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Cart\AddToCartRequest;
use App\Http\Requests\Storefront\Cart\UpdateCartRequest;
use App\Support\Storefront\Cart;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(Cart $cart): Response
    {
        $detailed = $cart->getDetailed();

        return Inertia::render('Storefront/Cart', [
            'items' => $detailed->map(fn (array $item) => [
                'product_id' => $item['product']->id,
                'name' => $item['product']->localized_name,
                'bonus_price' => $item['product']->bonus_price,
                'weight_grams' => $item['product']->weight_grams,
                'stock_quantity' => $item['product']->stock_quantity,
                'image' => $item['product']->image
                    ? route('image.show', ['path' => $item['product']->image, 'w' => 240, 'h' => 240, 'fit' => 'crop', 'fm' => 'webp', 'q' => 80], absolute: false)
                    : null,
                'quantity' => $item['quantity'],
                'line_total_bonus' => $item['line_total_bonus'],
                'line_total_weight_grams' => $item['line_total_weight_grams'],
            ]),
            'totalBonuses' => $detailed->sum('line_total_bonus'),
            'totalWeightGrams' => $detailed->sum('line_total_weight_grams'),
        ]);
    }

    public function store(AddToCartRequest $request, Cart $cart): RedirectResponse
    {
        $cart->add((int) $request->validated('product_id'), (int) $request->validated('quantity'));

        return back()->with('success', 'Товар добавлен в корзину.');
    }

    public function update(UpdateCartRequest $request, int $productId, Cart $cart): RedirectResponse
    {
        $cart->update($productId, (int) $request->validated('quantity'));

        return back()->with('success', 'Корзина обновлена.');
    }

    public function destroy(int $productId, Cart $cart): RedirectResponse
    {
        $cart->remove($productId);

        return back()->with('success', 'Товар удалён из корзины.');
    }
}
