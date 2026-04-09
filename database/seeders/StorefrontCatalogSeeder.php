<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StorefrontCatalogSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $languages = $this->ensureLanguages();
            $leafCategories = $this->seedCategories($languages);

            foreach (range(1, 500) as $index) {
                $this->seedProduct($index, $leafCategories, $languages);
            }
        });
    }

    /**
     * @return Collection<string, Language>
     */
    private function ensureLanguages(): Collection
    {
        foreach ([
            ['code' => 'ru', 'name' => 'Русский', 'sort_order' => 0, 'is_default' => true],
            ['code' => 'kk', 'name' => 'Қазақша', 'sort_order' => 1, 'is_default' => false],
            ['code' => 'en', 'name' => 'English', 'sort_order' => 2, 'is_default' => false],
        ] as $definition) {
            $language = Language::query()->firstOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'is_default' => $definition['is_default'],
                    'is_active' => true,
                    'sort_order' => $definition['sort_order'],
                ],
            );

            if (! $language->is_active || blank($language->name)) {
                $language->forceFill([
                    'name' => filled($language->name) ? $language->name : $definition['name'],
                    'is_active' => true,
                ])->save();
            }
        }

        return Language::query()
            ->whereIn('code', ['ru', 'kk', 'en'])
            ->get()
            ->keyBy('code');
    }

    /**
     * @param  Collection<string, Language>  $languages
     * @return array<int, array{category: Category, translations: array<string, string>}>
     */
    private function seedCategories(Collection $languages): array
    {
        $leafCategories = [];

        foreach ($this->categoryTree() as $parentIndex => $parentDefinition) {
            $parentCategory = Category::query()->updateOrCreate(
                ['slug' => $parentDefinition['slug']],
                [
                    'parent_id' => null,
                    'sort_order' => ($parentIndex + 1) * 10,
                    'is_active' => true,
                ],
            );

            $this->syncCategoryTranslations($parentCategory, $languages, $parentDefinition['translations']);

            foreach ($parentDefinition['children'] as $childIndex => $childDefinition) {
                $childCategory = Category::query()->updateOrCreate(
                    ['slug' => sprintf('%s-%s', $parentDefinition['slug'], $childDefinition['slug'])],
                    [
                        'parent_id' => $parentCategory->getKey(),
                        'sort_order' => ($childIndex + 1) * 10,
                        'is_active' => true,
                    ],
                );

                $this->syncCategoryTranslations($childCategory, $languages, $childDefinition['translations']);

                $leafCategories[] = [
                    'category' => $childCategory,
                    'translations' => $childDefinition['translations'],
                ];
            }
        }

        return $leafCategories;
    }

    /**
     * @param  array<int, array{category: Category, translations: array<string, string>}>  $leafCategories
     * @param  Collection<string, Language>  $languages
     */
    private function seedProduct(int $index, array $leafCategories, Collection $languages): void
    {
        $categoryPayload = $leafCategories[($index - 1) % count($leafCategories)];
        $weightGrams = $this->weightFor($index);
        $bonusPrice = 150 + (($index * 73) % 4_850);

        $product = Product::query()->updateOrCreate(
            ['slug' => sprintf('storefront-product-%03d', $index)],
            [
                'category_id' => $categoryPayload['category']->getKey(),
                'bonus_price' => $bonusPrice,
                'weight_grams' => $weightGrams,
                'stock_quantity' => 12 + (($index * 7) % 89),
                'image' => null,
                'is_active' => true,
            ],
        );

        $this->syncProductTranslations(
            $product,
            $languages,
            $this->productTranslations(
                $index,
                $categoryPayload['translations'],
                $weightGrams,
                $bonusPrice,
            ),
        );
    }

    private function weightFor(int $index): int
    {
        $weights = [80, 95, 110, 125, 180, 220, 250, 330, 450, 500, 750, 900, 1000];

        return $weights[($index - 1) % count($weights)];
    }

    /**
     * @param  array<string, string>  $categoryTranslations
     * @return array<string, array{name: string, description: string}>
     */
    private function productTranslations(
        int $index,
        array $categoryTranslations,
        int $weightGrams,
        int $bonusPrice,
    ): array {
        $namespaces = [
            'ru' => ['Фермерский', 'Городской', 'Домашний', 'Сезонный', 'Семейный', 'Отборный', 'Свежий', 'Премиум'],
            'kk' => ['Фермерлік', 'Қалалық', 'Үйлік', 'Маусымдық', 'Отбасылық', 'Таңдаулы', 'Балғын', 'Премиум'],
            'en' => ['Farm', 'Urban', 'Homestyle', 'Seasonal', 'Family', 'Select', 'Fresh', 'Premium'],
        ];

        $formats = [
            'ru' => ['мини', 'стандарт', 'ежедневный', 'макси', 'витринный'],
            'kk' => ['мини', 'стандарт', 'күнделікті', 'макси', 'витриналық'],
            'en' => ['Mini', 'Standard', 'Daily', 'Max', 'Display'],
        ];

        $sku = str_pad((string) $index, 3, '0', STR_PAD_LEFT);
        $translations = [];

        foreach ($categoryTranslations as $locale => $categoryName) {
            $series = $namespaces[$locale][($index - 1) % count($namespaces[$locale])];
            $format = $formats[$locale][intdiv($index - 1, count($namespaces[$locale])) % count($formats[$locale])];

            $name = $locale === 'en'
                ? "{$series} {$categoryName} {$format} {$sku}"
                : "{$categoryName} {$series} {$format} {$sku}";

            $description = match ($locale) {
                'ru' => "{$categoryName} из серии {$series}. Подходит для витрины магазина и тестовых заказов. Вес {$weightGrams} г, цена {$bonusPrice} бон.",
                'kk' => "{$series} желісіндегі {$categoryName}. Дүкен витринасы мен тест тапсырыстарына арналған. Салмағы {$weightGrams} г, бағасы {$bonusPrice} бон.",
                default => "{$series} {$categoryName} for the storefront catalog and test orders. Weight {$weightGrams} g, price {$bonusPrice} bonuses.",
            };

            $translations[$locale] = [
                'name' => $name,
                'description' => $description,
            ];
        }

        return $translations;
    }

    /**
     * @param  Collection<string, Language>  $languages
     * @param  array<string, string>  $translations
     */
    private function syncCategoryTranslations(Category $category, Collection $languages, array $translations): void
    {
        foreach ($translations as $locale => $name) {
            $language = $languages->get($locale);

            if (! $language instanceof Language) {
                continue;
            }

            $category->translations()->updateOrCreate(
                ['language_id' => $language->getKey()],
                ['name' => $name],
            );
        }
    }

    /**
     * @param  Collection<string, Language>  $languages
     * @param  array<string, array{name: string, description: string}>  $translations
     */
    private function syncProductTranslations(Product $product, Collection $languages, array $translations): void
    {
        foreach ($translations as $locale => $translation) {
            $language = $languages->get($locale);

            if (! $language instanceof Language) {
                continue;
            }

            $product->translations()->updateOrCreate(
                ['language_id' => $language->getKey()],
                $translation,
            );
        }
    }

    /**
     * @return array<int, array{
     *     slug: string,
     *     translations: array<string, string>,
     *     children: array<int, array{slug: string, translations: array<string, string>}>
     * }>
     */
    private function categoryTree(): array
    {
        return [
            [
                'slug' => 'storefront-snacks',
                'translations' => ['ru' => 'Снеки', 'kk' => 'Тіскебасарлар', 'en' => 'Snacks'],
                'children' => [
                    ['slug' => 'chips', 'translations' => ['ru' => 'Чипсы', 'kk' => 'Чипсылар', 'en' => 'Chips']],
                    ['slug' => 'nuts', 'translations' => ['ru' => 'Орехи', 'kk' => 'Жаңғақтар', 'en' => 'Nuts']],
                    ['slug' => 'crackers', 'translations' => ['ru' => 'Крекеры', 'kk' => 'Крекерлер', 'en' => 'Crackers']],
                    ['slug' => 'popcorn', 'translations' => ['ru' => 'Попкорн', 'kk' => 'Попкорн', 'en' => 'Popcorn']],
                ],
            ],
            [
                'slug' => 'storefront-drinks',
                'translations' => ['ru' => 'Напитки', 'kk' => 'Сусындар', 'en' => 'Drinks'],
                'children' => [
                    ['slug' => 'juice', 'translations' => ['ru' => 'Соки', 'kk' => 'Шырындар', 'en' => 'Juice']],
                    ['slug' => 'water', 'translations' => ['ru' => 'Вода', 'kk' => 'Су', 'en' => 'Water']],
                    ['slug' => 'soda', 'translations' => ['ru' => 'Газировка', 'kk' => 'Газды сусындар', 'en' => 'Soda']],
                    ['slug' => 'tea', 'translations' => ['ru' => 'Чай', 'kk' => 'Шай', 'en' => 'Tea']],
                ],
            ],
            [
                'slug' => 'storefront-sweets',
                'translations' => ['ru' => 'Сладости', 'kk' => 'Тәттілер', 'en' => 'Sweets'],
                'children' => [
                    ['slug' => 'chocolate', 'translations' => ['ru' => 'Шоколад', 'kk' => 'Шоколад', 'en' => 'Chocolate']],
                    ['slug' => 'candy', 'translations' => ['ru' => 'Конфеты', 'kk' => 'Кәмпиттер', 'en' => 'Candy']],
                    ['slug' => 'cookies', 'translations' => ['ru' => 'Печенье', 'kk' => 'Печенье', 'en' => 'Cookies']],
                    ['slug' => 'marshmallow', 'translations' => ['ru' => 'Зефир', 'kk' => 'Зефир', 'en' => 'Marshmallow']],
                ],
            ],
            [
                'slug' => 'storefront-dairy',
                'translations' => ['ru' => 'Молочные продукты', 'kk' => 'Сүт өнімдері', 'en' => 'Dairy'],
                'children' => [
                    ['slug' => 'milk', 'translations' => ['ru' => 'Молоко', 'kk' => 'Сүт', 'en' => 'Milk']],
                    ['slug' => 'yogurt', 'translations' => ['ru' => 'Йогурт', 'kk' => 'Йогурт', 'en' => 'Yogurt']],
                    ['slug' => 'cheese', 'translations' => ['ru' => 'Сыр', 'kk' => 'Ірімшік', 'en' => 'Cheese']],
                    ['slug' => 'butter', 'translations' => ['ru' => 'Масло', 'kk' => 'Сары май', 'en' => 'Butter']],
                ],
            ],
            [
                'slug' => 'storefront-breakfast',
                'translations' => ['ru' => 'Завтрак', 'kk' => 'Таңғы ас', 'en' => 'Breakfast'],
                'children' => [
                    ['slug' => 'cereals', 'translations' => ['ru' => 'Хлопья', 'kk' => 'Жармалар', 'en' => 'Cereals']],
                    ['slug' => 'granola', 'translations' => ['ru' => 'Гранола', 'kk' => 'Гранола', 'en' => 'Granola']],
                    ['slug' => 'jam', 'translations' => ['ru' => 'Джем', 'kk' => 'Тосап', 'en' => 'Jam']],
                    ['slug' => 'honey', 'translations' => ['ru' => 'Мёд', 'kk' => 'Бал', 'en' => 'Honey']],
                ],
            ],
            [
                'slug' => 'storefront-grains-pasta',
                'translations' => ['ru' => 'Крупы и паста', 'kk' => 'Жарма және макарон', 'en' => 'Grains & Pasta'],
                'children' => [
                    ['slug' => 'pasta', 'translations' => ['ru' => 'Макароны', 'kk' => 'Макарон', 'en' => 'Pasta']],
                    ['slug' => 'rice', 'translations' => ['ru' => 'Рис', 'kk' => 'Күріш', 'en' => 'Rice']],
                    ['slug' => 'buckwheat', 'translations' => ['ru' => 'Гречка', 'kk' => 'Қарақұмық', 'en' => 'Buckwheat']],
                    ['slug' => 'oats', 'translations' => ['ru' => 'Овсянка', 'kk' => 'Сұлы', 'en' => 'Oats']],
                ],
            ],
            [
                'slug' => 'storefront-canned',
                'translations' => ['ru' => 'Консервы', 'kk' => 'Консервілер', 'en' => 'Canned Goods'],
                'children' => [
                    ['slug' => 'beans', 'translations' => ['ru' => 'Фасоль', 'kk' => 'Бұршақ', 'en' => 'Beans']],
                    ['slug' => 'corn', 'translations' => ['ru' => 'Кукуруза', 'kk' => 'Жүгері', 'en' => 'Corn']],
                    ['slug' => 'peas', 'translations' => ['ru' => 'Горошек', 'kk' => 'Жасыл бұршақ', 'en' => 'Peas']],
                    ['slug' => 'sauces', 'translations' => ['ru' => 'Соусы', 'kk' => 'Тұздықтар', 'en' => 'Sauces']],
                ],
            ],
            [
                'slug' => 'storefront-frozen',
                'translations' => ['ru' => 'Заморозка', 'kk' => 'Мұздатылған өнімдер', 'en' => 'Frozen'],
                'children' => [
                    ['slug' => 'dumplings', 'translations' => ['ru' => 'Пельмени', 'kk' => 'Тұшпара', 'en' => 'Dumplings']],
                    ['slug' => 'vegetables', 'translations' => ['ru' => 'Овощи', 'kk' => 'Көкөністер', 'en' => 'Vegetables']],
                    ['slug' => 'pizza', 'translations' => ['ru' => 'Пицца', 'kk' => 'Пицца', 'en' => 'Pizza']],
                    ['slug' => 'ice-cream', 'translations' => ['ru' => 'Мороженое', 'kk' => 'Балмұздақ', 'en' => 'Ice Cream']],
                ],
            ],
            [
                'slug' => 'storefront-household',
                'translations' => ['ru' => 'Для дома', 'kk' => 'Үйге арналған', 'en' => 'Household'],
                'children' => [
                    ['slug' => 'soap', 'translations' => ['ru' => 'Мыло', 'kk' => 'Сабын', 'en' => 'Soap']],
                    ['slug' => 'detergent', 'translations' => ['ru' => 'Средства для стирки', 'kk' => 'Жуу құралдары', 'en' => 'Detergent']],
                    ['slug' => 'paper-goods', 'translations' => ['ru' => 'Бумажные товары', 'kk' => 'Қағаз тауарлары', 'en' => 'Paper Goods']],
                    ['slug' => 'cleaning', 'translations' => ['ru' => 'Для уборки', 'kk' => 'Тазалық құралдары', 'en' => 'Cleaning']],
                ],
            ],
            [
                'slug' => 'storefront-kids',
                'translations' => ['ru' => 'Для детей', 'kk' => 'Балаларға арналған', 'en' => 'Kids'],
                'children' => [
                    ['slug' => 'purees', 'translations' => ['ru' => 'Пюре', 'kk' => 'Пюре', 'en' => 'Purees']],
                    ['slug' => 'baby-porridge', 'translations' => ['ru' => 'Детские каши', 'kk' => 'Балалар ботқасы', 'en' => 'Baby Porridge']],
                    ['slug' => 'kids-juice', 'translations' => ['ru' => 'Детские соки', 'kk' => 'Балалар шырыны', 'en' => 'Kids Juice']],
                    ['slug' => 'kids-snacks', 'translations' => ['ru' => 'Детские снеки', 'kk' => 'Балалар тіскебасарлары', 'en' => 'Kids Snacks']],
                ],
            ],
        ];
    }
}
