<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderController extends Controller
{
    public function index(): Response
    {
        /** @var Client $client */
        $client = Auth::guard('client')->user();

        $orders = $client->orders()
            ->latest('placed_at')
            ->paginate(10);

        return Inertia::render('Storefront/Orders', [
            'orders' => $orders->through(fn (Order $order) => [
                'id' => $order->id,
                'url' => route('storefront.orders.show', $order, false),
                'status' => $order->status->value,
                'total_bonus' => $order->total_bonus,
                'total_weight_grams' => $order->total_weight_grams,
                'placed_at' => $order->placed_at?->format('d.m.Y H:i'),
            ]),
        ]);
    }

    public function show(Order $order): Response
    {
        /** @var Client $client */
        $client = Auth::guard('client')->user();

        if ($order->client_id !== $client->id) {
            throw new NotFoundHttpException;
        }

        $order->load([
            'items',
            'statusHistories' => fn ($q) => $q->latest(),
            'institution' => fn ($q) => $q->withLocalizedName(),
        ]);

        return Inertia::render('Storefront/OrderDetail', [
            'order' => [
                'id' => $order->id,
                'status' => $order->status->value,
                'total_bonus' => $order->total_bonus,
                'total_weight_grams' => $order->total_weight_grams,
                'placed_at' => $order->placed_at?->format('d.m.Y H:i'),
                'delivered_at' => $order->delivered_at?->format('d.m.Y H:i'),
                'cancelled_at' => $order->cancelled_at?->format('d.m.Y H:i'),
                'institution' => $order->institution ? [
                    'id' => $order->institution->id,
                    'name' => $order->institution->localized_name,
                ] : null,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'product_image' => $item->product_image
                        ? route('image.show', ['path' => $item->product_image, 'w' => 160, 'h' => 160, 'fit' => 'crop', 'fm' => 'webp', 'q' => 78], absolute: false)
                        : null,
                    'price_bonus' => $item->price_bonus,
                    'weight_grams' => $item->weight_grams,
                    'quantity' => $item->quantity,
                    'line_total_bonus' => $item->line_total_bonus,
                ]),
                'status_histories' => $order->statusHistories->map(fn ($h) => [
                    'from_status' => $h->from_status?->value,
                    'to_status' => $h->to_status->value,
                    'comment' => $h->comment,
                    'created_at' => $h->created_at?->format('d.m.Y H:i'),
                ]),
            ],
        ]);
    }
}
