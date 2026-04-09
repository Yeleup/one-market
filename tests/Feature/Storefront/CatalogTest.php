<?php

use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('renders the catalog page', function () {
    $this->get(route('storefront.catalog'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Storefront/Catalog'));
});

it('only shows active products', function () {
    Product::factory()->create(['is_active' => true]);
    Product::factory()->create(['is_active' => false]);

    $this->get(route('storefront.catalog'))
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/Catalog')
            ->has('products.data', 1)
        );
});

it('passes parent categories with nested children', function (): void {
    $language = Language::factory()->default()->create(['code' => 'en']);

    $parentCategory = Category::factory()->create();
    $parentCategory->translations()->create([
        'language_id' => $language->getKey(),
        'name' => 'Snacks',
    ]);

    $childCategory = Category::factory()->create([
        'parent_id' => $parentCategory->getKey(),
    ]);
    $childCategory->translations()->create([
        'language_id' => $language->getKey(),
        'name' => 'Chips',
    ]);

    $this->get(route('storefront.catalog'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/Catalog')
            ->has('categories', 1)
            ->where('categories.0.id', $parentCategory->getKey())
            ->where('categories.0.name', 'Snacks')
            ->has('categories.0.children', 1)
            ->where('categories.0.children.0.id', $childCategory->getKey())
            ->where('categories.0.children.0.name', 'Chips')
        );
});

it('renders a single product page', function () {
    $product = Product::factory()->create(['is_active' => true]);

    $this->get(route('storefront.products.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/Product')
            ->has('product')
        );
});

it('passes the main image separately from additional images on the product page', function (): void {
    Storage::fake('public');

    $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR42mNk+M/wHwAEAQH/cetH5QAAAABJRU5ErkJggg==');

    $mainImage = 'products/main-image.png';
    $galleryImageOne = 'products/gallery-image-1.png';
    $galleryImageTwo = 'products/gallery-image-2.png';

    Storage::disk('public')->put($mainImage, $imageContent);
    Storage::disk('public')->put($galleryImageOne, $imageContent);
    Storage::disk('public')->put($galleryImageTwo, $imageContent);

    $product = Product::factory()->create([
        'is_active' => true,
        'image' => $mainImage,
    ]);

    $product->images()->createMany([
        [
            'image' => $galleryImageOne,
            'sort_order' => 1,
        ],
        [
            'image' => $galleryImageTwo,
            'sort_order' => 2,
        ],
    ]);

    $this->get(route('storefront.products.show', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('product.image', fn (string $url) => str_starts_with($url, '/img/') && str_contains($url, 'w=1400') && str_contains($url, 'fm=webp'))
            ->where('product.image_thumb', fn (string $url) => str_starts_with($url, '/img/') && str_contains($url, 'w=240') && str_contains($url, 'fm=webp'))
            ->has('product.images', 2)
            ->where('product.images.0.image', fn (string $url) => str_starts_with($url, '/img/') && str_contains($url, 'w=1400'))
            ->where('product.images.0.thumb', fn (string $url) => str_starts_with($url, '/img/') && str_contains($url, 'w=240'))
            ->where('product.images.1.image', fn (string $url) => str_starts_with($url, '/img/') && str_contains($url, 'w=1400'))
            ->where('product.images.1.thumb', fn (string $url) => str_starts_with($url, '/img/') && str_contains($url, 'w=240'))
        );
});

it('returns an image route url for catalog cards', function (): void {
    Storage::fake('public');

    $imagePath = 'products/catalog-image.png';

    Storage::disk('public')->put(
        $imagePath,
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR42mNk+M/wHwAEAQH/cetH5QAAAABJRU5ErkJggg==')
    );

    $product = Product::factory()->create([
        'is_active' => true,
        'image' => $imagePath,
    ]);

    $response = $this->get(route('storefront.catalog'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.url', route('storefront.products.show', $product, false))
            ->where('products.data.0.image', fn (string $url) => str_starts_with($url, '/img/') && str_contains($url, 'w=640') && str_contains($url, 'fm=webp'))
        );
});
