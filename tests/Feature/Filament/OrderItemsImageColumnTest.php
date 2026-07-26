<?php

use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\RelationManagers\ItemsRelationManager;
use App\Models\Language;
use App\Models\Order;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->default()->create(['code' => 'en']);
    app()->setLocale('en');

    Filament::setCurrentPanel('admin');

    $this->actingAs(User::factory()->create());
});

it('renders a resized image url for order items stored on the public disk', function (): void {
    Storage::fake('public');

    $imagePath = 'products/order-item-image.png';
    Storage::disk('public')->put($imagePath, 'fake-image-content');

    $order = Order::factory()->create();
    $order->items()->create([
        'product_name' => 'Milk',
        'product_image' => $imagePath,
        'price_bonus' => 100,
        'weight_grams' => 500,
        'quantity' => 1,
        'line_total_bonus' => 100,
        'line_total_weight_grams' => 500,
    ]);

    $expectedUrl = route('image.show', [
        'path' => $imagePath,
        'w' => 240,
        'h' => 240,
        'fit' => 'crop',
        'fm' => 'webp',
        'q' => 80,
    ]);

    Livewire::test(ItemsRelationManager::class, [
        'ownerRecord' => $order,
        'pageClass' => EditOrder::class,
    ])
        ->assertSuccessful()
        ->assertSee($expectedUrl, escape: false);
});

it('renders items without an image without errors', function (): void {
    $order = Order::factory()->create();
    $order->items()->create([
        'product_name' => 'No image item',
        'product_image' => null,
        'price_bonus' => 100,
        'weight_grams' => 500,
        'quantity' => 1,
        'line_total_bonus' => 100,
        'line_total_weight_grams' => 500,
    ]);

    Livewire::test(ItemsRelationManager::class, [
        'ownerRecord' => $order,
        'pageClass' => EditOrder::class,
    ])
        ->assertSuccessful();
});
