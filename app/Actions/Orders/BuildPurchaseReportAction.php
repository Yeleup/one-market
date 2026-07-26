<?php

namespace App\Actions\Orders;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class BuildPurchaseReportAction
{
    /**
     * Build a procurement report from the given orders, grouped by category and,
     * within each product, broken down by the client orders that requested it.
     *
     * @param  Collection<int, Order>  $orders
     * @return array{
     *     categories: array<int, array{
     *         name: string,
     *         total_quantity: int,
     *         total_weight_grams: int,
     *         products: array<int, array{
     *             name: string,
     *             total_quantity: int,
     *             total_weight_grams: int,
     *             lines: array<int, array{order_id: int, client_name: string, quantity: int}>,
     *         }>,
     *     }>,
     *     grand_total_quantity: int,
     * }
     */
    public function handle(Collection $orders): array
    {
        $categories = [];

        foreach ($orders as $order) {
            $clientName = $this->resolveClientName($order);

            foreach ($order->items as $item) {
                $category = $item->product?->category;
                $categoryKey = $category?->getKey() ?? 0;
                $productKey = $item->product_id !== null
                    ? 'id:'.$item->product_id
                    : 'name:'.$item->product_name;
                $orderKey = (int) $order->getKey();

                $categories[$categoryKey] ??= [
                    'name' => $category?->localized_name
                        ?: __('admin.resources.order.purchase_report.uncategorized'),
                    'sort_order' => $category?->sort_order ?? PHP_INT_MAX,
                    'products' => [],
                    'total_quantity' => 0,
                    'total_weight_grams' => 0,
                ];

                $categories[$categoryKey]['products'][$productKey] ??= [
                    'name' => $item->product_name,
                    'total_quantity' => 0,
                    'total_weight_grams' => 0,
                    'lines' => [],
                ];

                $categories[$categoryKey]['products'][$productKey]['lines'][$orderKey] ??= [
                    'order_id' => $orderKey,
                    'client_name' => $clientName,
                    'quantity' => 0,
                ];

                $categories[$categoryKey]['products'][$productKey]['lines'][$orderKey]['quantity'] += $item->quantity;
                $categories[$categoryKey]['products'][$productKey]['total_quantity'] += $item->quantity;
                $categories[$categoryKey]['products'][$productKey]['total_weight_grams'] += $item->line_total_weight_grams;
                $categories[$categoryKey]['total_quantity'] += $item->quantity;
                $categories[$categoryKey]['total_weight_grams'] += $item->line_total_weight_grams;
            }
        }

        return $this->normalize($categories);
    }

    /**
     * @param  array<int, array<string, mixed>>  $categories
     * @return array{categories: array<int, array<string, mixed>>, grand_total_quantity: int}
     */
    private function normalize(array $categories): array
    {
        $normalizedCategories = collect($categories)
            ->sortBy([['sort_order', 'asc'], ['name', 'asc']])
            ->map(fn (array $category): array => [
                'name' => $category['name'],
                'total_quantity' => $category['total_quantity'],
                'total_weight_grams' => $category['total_weight_grams'],
                'products' => collect($category['products'])
                    ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                    ->map(fn (array $product): array => [
                        ...$product,
                        'lines' => collect($product['lines'])
                            ->sortBy('order_id')
                            ->values()
                            ->all(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return [
            'categories' => $normalizedCategories,
            'grand_total_quantity' => (int) collect($normalizedCategories)->sum('total_quantity'),
        ];
    }

    private function resolveClientName(Order $order): string
    {
        $clientName = trim((string) $order->client?->full_name);

        if ($clientName !== '') {
            return $clientName;
        }

        $recipientName = trim($order->recipient_full_name);

        return $recipientName !== ''
            ? $recipientName
            : __('admin.common.messages.untitled');
    }
}
