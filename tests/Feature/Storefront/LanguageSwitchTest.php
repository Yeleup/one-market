<?php

use App\Http\Middleware\HandleStorefrontInertiaRequests;
use App\Models\Category;
use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores the selected storefront language in the session', function (): void {
    Language::factory()->default()->create([
        'code' => 'ru',
        'name' => 'Русский',
        'sort_order' => 0,
    ]);
    Language::factory()->create([
        'code' => 'kk',
        'name' => 'Қазақша',
        'sort_order' => 1,
    ]);

    $this->from(route('storefront.catalog'))
        ->post(route('storefront.language'), [
            'language' => 'kk',
        ])
        ->assertRedirect(route('storefront.catalog'))
        ->assertSessionHas(HandleStorefrontInertiaRequests::SESSION_KEY, 'kk');
});

it('rejects inactive storefront languages', function (): void {
    Language::factory()->default()->create([
        'code' => 'ru',
        'name' => 'Русский',
    ]);
    Language::factory()->create([
        'code' => 'en',
        'name' => 'English',
        'is_active' => false,
    ]);

    $this->from(route('storefront.catalog'))
        ->post(route('storefront.language'), [
            'language' => 'en',
        ])
        ->assertRedirect(route('storefront.catalog'))
        ->assertSessionHasErrors('language');
});

it('shares available languages and applies the selected storefront locale', function (): void {
    $defaultLanguage = Language::factory()->default()->create([
        'code' => 'ru',
        'name' => 'Русский',
        'sort_order' => 0,
    ]);
    $kazakhLanguage = Language::factory()->create([
        'code' => 'kk',
        'name' => 'Қазақша',
        'sort_order' => 1,
    ]);
    Language::factory()->create([
        'code' => 'en',
        'name' => 'English',
        'sort_order' => 2,
        'is_active' => false,
    ]);

    $category = Category::factory()->create();
    $category->translations()->createMany([
        [
            'language_id' => $defaultLanguage->getKey(),
            'name' => 'Снеки',
        ],
        [
            'language_id' => $kazakhLanguage->getKey(),
            'name' => 'Тіскебасар',
        ],
    ]);

    $this->withSession([
        HandleStorefrontInertiaRequests::SESSION_KEY => 'kk',
    ])->get(route('storefront.catalog'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/Catalog')
            ->where('locale.current', 'kk')
            ->has('locale.available', 2)
            ->where('locale.available.0.code', 'ru')
            ->where('locale.available.1.code', 'kk')
            ->where('categories.0.name', 'Тіскебасар')
        );
});
