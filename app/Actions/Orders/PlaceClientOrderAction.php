<?php

namespace App\Actions\Orders;

use App\Enums\OrderSource;
use App\Models\Client;
use App\Models\Institution;
use App\Models\Order;
use App\Models\Product;
use App\Support\Storefront\Cart;
use App\Support\Translations\CurrentTranslationResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceClientOrderAction
{
    public function __construct(
        private CreateOrderAction $createOrderAction,
    ) {}

    public function handle(Client $client, Cart $cart, int $institutionId): Order
    {
        if ($cart->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Корзина пуста.',
            ]);
        }

        return DB::transaction(function () use ($client, $cart, $institutionId): Order {
            $cartItems = $cart->items();

            $products = Product::query()
                ->whereIn('id', array_keys($cartItems))
                ->lockForUpdate()
                ->with('translations', 'images')
                ->get()
                ->keyBy('id');

            $totalBonus = 0;
            $totalWeightGrams = 0;
            $orderItems = [];

            foreach ($cartItems as $productId => $quantity) {
                /** @var Product|null $product */
                $product = $products->get($productId);

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => 'Товар недоступен для заказа.',
                    ]);
                }

                if ($product->stock_quantity < $quantity) {
                    $name = CurrentTranslationResolver::name($product) ?? "ID {$productId}";

                    throw ValidationException::withMessages([
                        'cart' => "Недостаточно товара «{$name}» на складе.",
                    ]);
                }

                $lineBonus = $product->bonus_price * $quantity;
                $lineWeight = $product->weight_grams * $quantity;

                $totalBonus += $lineBonus;
                $totalWeightGrams += $lineWeight;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => CurrentTranslationResolver::name($product) ?? '',
                    'product_image' => $product->image,
                    'price_bonus' => $product->bonus_price,
                    'weight_grams' => $product->weight_grams,
                    'quantity' => $quantity,
                    'line_total_bonus' => $lineBonus,
                    'line_total_weight_grams' => $lineWeight,
                ];
            }

            $institution = Institution::query()->findOrFail($institutionId);

            if (! $institution->is_active) {
                throw ValidationException::withMessages([
                    'institution_id' => 'Выбранное учреждение недоступно.',
                ]);
            }

            if ($totalWeightGrams > $institution->max_weight_grams) {
                throw ValidationException::withMessages([
                    'cart' => "Общий вес корзины ({$totalWeightGrams} г) превышает лимит учреждения ({$institution->max_weight_grams} г).",
                ]);
            }

            $order = $this->createOrderAction->handle(
                attributes: [
                    'client_id' => $client->id,
                    'institution_id' => $institutionId,
                    'total_bonus' => $totalBonus,
                    'total_weight_grams' => $totalWeightGrams,
                    'placed_at' => now(),
                ],
                source: OrderSource::Client,
            );

            $order->items()->createMany($orderItems);

            foreach ($cartItems as $productId => $quantity) {
                Product::query()
                    ->where('id', $productId)
                    ->decrement('stock_quantity', $quantity);
            }

            $cart->clear();

            return $order;
        });
    }
}
