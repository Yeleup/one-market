<?php

namespace App\Support\Storefront;

use App\Models\Product;
use Illuminate\Support\Collection;

class Cart
{
    private const SESSION_KEY = 'storefront_cart';

    /**
     * @var array<int, int> product_id => quantity
     */
    private array $items;

    public function __construct()
    {
        $this->items = session(self::SESSION_KEY, []);
    }

    /**
     * @return array<int, int>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function add(int $productId, int $quantity = 1): void
    {
        $current = $this->items[$productId] ?? 0;
        $this->items[$productId] = $current + $quantity;
        $this->save();
    }

    public function update(int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->remove($productId);

            return;
        }

        $this->items[$productId] = $quantity;
        $this->save();
    }

    public function remove(int $productId): void
    {
        unset($this->items[$productId]);
        $this->save();
    }

    public function clear(): void
    {
        $this->items = [];
        $this->save();
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function totalQuantity(): int
    {
        return array_sum($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * @return Collection<int, array{product: Product, quantity: int, line_total_bonus: int, line_total_weight_grams: int}>
     */
    public function getDetailed(): Collection
    {
        if ($this->isEmpty()) {
            return collect();
        }

        $products = Product::query()
            ->whereIn('id', array_keys($this->items))
            ->where('is_active', true)
            ->withLocalizedName()
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')->limit(1)])
            ->get()
            ->keyBy('id');

        return collect($this->items)
            ->map(function (int $quantity, int $productId) use ($products): ?array {
                $product = $products->get($productId);

                if (! $product) {
                    return null;
                }

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                    'line_total_bonus' => $product->bonus_price * $quantity,
                    'line_total_weight_grams' => $product->weight_grams * $quantity,
                ];
            })
            ->filter()
            ->values();
    }

    public function totalBonuses(): int
    {
        return $this->getDetailed()->sum('line_total_bonus');
    }

    public function totalWeightGrams(): int
    {
        return $this->getDetailed()->sum('line_total_weight_grams');
    }

    private function save(): void
    {
        session([self::SESSION_KEY => $this->items]);
    }
}
