<?php

use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->language = Language::factory()->default()->create(['code' => 'en']);
    app()->setLocale('en');

    Filament::setCurrentPanel('admin');

    $this->actingAs(User::factory()->create());
});

it('renders a resized image url instead of the full-size original', function (): void {
    Storage::fake('public');

    $imagePath = 'products/catalog-image.png';
    Storage::disk('public')->put($imagePath, 'fake-image-content');

    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->getKey(),
        'image' => $imagePath,
    ]);

    $expectedUrl = route('image.show', [
        'path' => $imagePath,
        'w' => 240,
        'h' => 240,
        'fit' => 'crop',
        'fm' => 'webp',
        'q' => 80,
    ]);

    Livewire::test(ListProducts::class)
        ->assertSee($expectedUrl, escape: false)
        ->assertDontSee(Storage::disk('public')->url($imagePath), escape: false);
});

it('renders no image state for products without an image', function (): void {
    $category = Category::factory()->create();
    Product::factory()->create([
        'category_id' => $category->getKey(),
        'image' => null,
    ]);

    Livewire::test(ListProducts::class)
        ->assertSuccessful();
});
