<?php

use App\Actions\Orders\BuildPurchaseReportAction;
use App\Models\Category;
use App\Models\Client;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $attributes
 */
function createReportItem(Order $order, ?Product $product, string $name, int $quantity, array $attributes = []): OrderItem
{
    return OrderItem::query()->create([
        'order_id' => $order->getKey(),
        'product_id' => $product?->getKey(),
        'product_name' => $name,
        'product_image' => null,
        'price_bonus' => 100,
        'weight_grams' => 500,
        'quantity' => $quantity,
        'line_total_bonus' => 100 * $quantity,
        'line_total_weight_grams' => 500 * $quantity,
        ...$attributes,
    ]);
}

/**
 * @param  array<int, int>  $orderIds
 * @return Collection<int, Order>
 */
function loadReportOrders(array $orderIds): Collection
{
    return Order::query()
        ->whereKey($orderIds)
        ->with(['client', 'items.product.category.translations'])
        ->get();
}

beforeEach(function (): void {
    $this->language = Language::factory()->default()->create(['code' => 'en']);
    app()->setLocale('en');
});

it('groups items by category and breaks each product down by client orders', function (): void {
    $dairy = Category::factory()->create(['sort_order' => 1]);
    $dairy->translations()->create(['language_id' => $this->language->getKey(), 'name' => 'Dairy']);

    $bakery = Category::factory()->create(['sort_order' => 2]);
    $bakery->translations()->create(['language_id' => $this->language->getKey(), 'name' => 'Bakery']);

    $milk = Product::factory()->create(['category_id' => $dairy->getKey()]);
    $cheese = Product::factory()->create(['category_id' => $dairy->getKey()]);
    $bread = Product::factory()->create(['category_id' => $bakery->getKey()]);

    $clientAnna = Client::factory()->create(['first_name' => 'Anna', 'last_name' => 'Alpha']);
    $clientBoris = Client::factory()->create(['first_name' => 'Boris', 'last_name' => 'Beta']);

    $orderAnna = Order::factory()->create(['client_id' => $clientAnna->getKey()]);
    $orderBoris = Order::factory()->create(['client_id' => $clientBoris->getKey()]);

    createReportItem($orderAnna, $milk, 'Milk', 2);
    createReportItem($orderAnna, $bread, 'Bread', 1);
    createReportItem($orderBoris, $milk, 'Milk', 3);
    createReportItem($orderBoris, $cheese, 'Cheese', 1);

    $report = app(BuildPurchaseReportAction::class)->handle(
        loadReportOrders([$orderAnna->getKey(), $orderBoris->getKey()]),
    );

    // Categories are ordered by sort_order: Dairy (1) then Bakery (2).
    expect($report['categories'])->toHaveCount(2)
        ->and($report['categories'][0]['name'])->toBe('Dairy')
        ->and($report['categories'][0]['total_quantity'])->toBe(6)
        ->and($report['categories'][1]['name'])->toBe('Bakery')
        ->and($report['categories'][1]['total_quantity'])->toBe(1)
        ->and($report['grand_total_quantity'])->toBe(7);

    $dairyProducts = collect($report['categories'][0]['products'])->keyBy('name');

    // Milk is requested across both client orders and totals to 5 units.
    expect($dairyProducts['Milk']['total_quantity'])->toBe(5)
        ->and($dairyProducts['Milk']['total_weight_grams'])->toBe(2500)
        ->and($dairyProducts['Milk']['lines'])->toHaveCount(2)
        ->and($dairyProducts['Milk']['lines'][0])->toMatchArray([
            'order_id' => $orderAnna->getKey(),
            'client_name' => 'Anna Alpha',
            'quantity' => 2,
        ])
        ->and($dairyProducts['Milk']['lines'][1])->toMatchArray([
            'order_id' => $orderBoris->getKey(),
            'client_name' => 'Boris Beta',
            'quantity' => 3,
        ]);

    // Cheese only appears in Boris' order.
    expect($dairyProducts['Cheese']['total_quantity'])->toBe(1)
        ->and($dairyProducts['Cheese']['lines'])->toHaveCount(1)
        ->and($dairyProducts['Cheese']['lines'][0]['client_name'])->toBe('Boris Beta');
});

it('merges duplicate product lines within the same order', function (): void {
    $category = Category::factory()->create();
    $category->translations()->create(['language_id' => $this->language->getKey(), 'name' => 'Grocery']);

    $product = Product::factory()->create(['category_id' => $category->getKey()]);
    $order = Order::factory()->create();

    createReportItem($order, $product, 'Sugar', 2);
    createReportItem($order, $product, 'Sugar', 3);

    $report = app(BuildPurchaseReportAction::class)->handle(
        loadReportOrders([$order->getKey()]),
    );

    $products = $report['categories'][0]['products'];

    expect($products)->toHaveCount(1)
        ->and($products[0]['total_quantity'])->toBe(5)
        ->and($products[0]['lines'])->toHaveCount(1)
        ->and($products[0]['lines'][0]['quantity'])->toBe(5);
});

it('places items without a category into an uncategorized group sorted last', function (): void {
    $category = Category::factory()->create(['sort_order' => 1]);
    $category->translations()->create(['language_id' => $this->language->getKey(), 'name' => 'Drinks']);

    $product = Product::factory()->create(['category_id' => $category->getKey()]);
    $order = Order::factory()->create();

    createReportItem($order, $product, 'Water', 1);
    // Legacy item whose product no longer exists (product_id is null).
    createReportItem($order, null, 'Legacy Item', 4);

    $report = app(BuildPurchaseReportAction::class)->handle(
        loadReportOrders([$order->getKey()]),
    );

    expect($report['categories'])->toHaveCount(2)
        ->and($report['categories'][0]['name'])->toBe('Drinks')
        ->and($report['categories'][1]['name'])->toBe(__('admin.resources.order.purchase_report.uncategorized'))
        ->and($report['categories'][1]['products'][0]['name'])->toBe('Legacy Item')
        ->and($report['categories'][1]['products'][0]['total_quantity'])->toBe(4);
});

it('renders the purchase report pdf view for the selected orders', function (): void {
    $category = Category::factory()->create();
    $category->translations()->create(['language_id' => $this->language->getKey(), 'name' => 'Dairy']);

    $product = Product::factory()->create(['category_id' => $category->getKey()]);
    $client = Client::factory()->create(['first_name' => 'Anna', 'last_name' => 'Alpha']);
    $order = Order::factory()->create(['client_id' => $client->getKey()]);

    createReportItem($order, $product, 'Milk', 2);

    $orders = loadReportOrders([$order->getKey()]);
    $report = app(BuildPurchaseReportAction::class)->handle($orders);

    $html = view('pdf.orders.purchase-report', [
        'report' => $report,
        'orders' => $orders,
        'generatedAt' => now(),
    ])->render();

    expect($html)
        ->toContain(__('admin.resources.order.purchase_report.title'))
        ->toContain('Dairy')
        ->toContain('Milk')
        ->toContain('Anna Alpha')
        ->toContain('#'.$order->getKey());
});
