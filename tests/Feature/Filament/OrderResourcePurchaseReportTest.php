<?php

use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Models\Category;
use App\Models\Client;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->language = Language::factory()->default()->create(['code' => 'en']);
    app()->setLocale('en');

    Filament::setCurrentPanel('admin');

    $this->actingAs(User::factory()->create());
});

it('streams a purchase report pdf from the orders bulk action', function (): void {
    $category = Category::factory()->create();
    $category->translations()->create(['language_id' => $this->language->getKey(), 'name' => 'Dairy']);

    $product = Product::factory()->create(['category_id' => $category->getKey()]);

    $orderOne = Order::factory()->create(['client_id' => Client::factory()]);
    $orderTwo = Order::factory()->create(['client_id' => Client::factory()]);

    foreach ([$orderOne, $orderTwo] as $order) {
        OrderItem::query()->create([
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
            'product_name' => 'Milk',
            'price_bonus' => 100,
            'weight_grams' => 500,
            'quantity' => 2,
            'line_total_bonus' => 200,
            'line_total_weight_grams' => 1000,
        ]);
    }

    Livewire::test(ListOrders::class)
        ->assertTableBulkActionExists('purchaseReport')
        ->callTableBulkAction('purchaseReport', [$orderOne->getKey(), $orderTwo->getKey()])
        ->assertFileDownloaded('purchase-report-'.now()->format('Y-m-d').'.pdf');
});
