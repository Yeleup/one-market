<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $categoryId = $request->integer('category');

        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->withLocalizedName()
            ->with(['children' => fn ($q) => $q->where('is_active', true)->withLocalizedName()->ordered()])
            ->ordered()
            ->get()
            ->map(fn (Category $cat) => [
                'id' => $cat->id,
                'name' => $cat->localized_name,
                'children' => $cat->children->map(fn (Category $child) => [
                    'id' => $child->id,
                    'name' => $child->localized_name,
                ]),
            ]);

        $products = Product::query()
            ->where('is_active', true)
            ->when($categoryId, function ($query) use ($categoryId) {
                $categoryIds = Category::query()
                    ->where('id', $categoryId)
                    ->orWhere('parent_id', $categoryId)
                    ->pluck('id');

                $query->whereIn('category_id', $categoryIds);
            })
            ->when($request->filled('search'), fn ($q) => $q->searchLocalizedName($request->string('search')))
            ->withLocalizedName()
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Storefront/Catalog', [
            'categories' => $categories,
            'products' => $products->through(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->localized_name,
                'url' => route('storefront.products.show', $product, false),
                'bonus_price' => $product->bonus_price,
                'weight_grams' => $product->weight_grams,
                'stock_quantity' => $product->stock_quantity,
                'image' => $product->image
                    ? route('image.show', ['path' => $product->image, 'w' => 640, 'h' => 640, 'fit' => 'crop', 'fm' => 'webp', 'q' => 82], absolute: false)
                    : null,
            ]),
            'filters' => [
                'category' => $categoryId ?: null,
                'search' => $request->string('search')->toString() ?: null,
            ],
        ]);
    }

    public function show(Product $product): Response
    {
        $product->load([
            'translations',
            'images' => fn ($q) => $q->orderBy('sort_order'),
            'category' => fn ($q) => $q->withLocalizedName(),
        ]);

        return Inertia::render('Storefront/Product', [
            'product' => [
                'id' => $product->id,
                'name' => $product->localized_name,
                'bonus_price' => $product->bonus_price,
                'weight_grams' => $product->weight_grams,
                'stock_quantity' => $product->stock_quantity,
                'is_active' => $product->is_active,
                'image' => $product->image
                    ? route('image.show', ['path' => $product->image, 'w' => 1400, 'h' => 1400, 'fit' => 'crop', 'fm' => 'webp', 'q' => 84], absolute: false)
                    : null,
                'image_thumb' => $product->image
                    ? route('image.show', ['path' => $product->image, 'w' => 240, 'h' => 240, 'fit' => 'crop', 'fm' => 'webp', 'q' => 80], absolute: false)
                    : null,
                'images' => $product->images->map(fn ($image): array => [
                    'id' => $image->id,
                    'image' => route('image.show', ['path' => $image->image, 'w' => 1400, 'h' => 1400, 'fit' => 'crop', 'fm' => 'webp', 'q' => 84], absolute: false),
                    'thumb' => route('image.show', ['path' => $image->image, 'w' => 240, 'h' => 240, 'fit' => 'crop', 'fm' => 'webp', 'q' => 80], absolute: false),
                ])->values(),
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->localized_name,
                ] : null,
            ],
        ]);
    }
}
