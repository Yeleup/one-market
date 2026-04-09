<script>
    import { router, usePage } from '@inertiajs/svelte';
    import { useStorefrontTranslations } from './i18n.js';
    import Layout from './Layout.svelte';

    let { categories, products, filters } = $props();
    const page = usePage();
    const { t } = useStorefrontTranslations();

    let search = $state(filters.search ?? '');
    let selectedCategoryId = $derived.by(() => {
        if (filters.category === null || filters.category === undefined || filters.category === '') {
            return null;
        }

        return Number(filters.category);
    });
    let selectedParentCategory = $derived.by(() => {
        if (selectedCategoryId === null) {
            return null;
        }

        return categories.find((category) => {
            if (category.id === selectedCategoryId) {
                return true;
            }

            return category.children?.some((child) => child.id === selectedCategoryId);
        }) ?? null;
    });
    let selectedChildCategory = $derived.by(() => {
        if (selectedParentCategory === null || selectedCategoryId === null) {
            return null;
        }

        return selectedParentCategory.children?.find((child) => child.id === selectedCategoryId) ?? null;
    });
    let isBrowsingChildren = $derived.by(() => {
        return selectedParentCategory !== null && (selectedParentCategory.children?.length ?? 0) > 0;
    });
    let visibleCategories = $derived.by(() => {
        if (isBrowsingChildren) {
            return selectedParentCategory.children;
        }

        return categories;
    });
    let backCategoryId = $derived.by(() => {
        if (!isBrowsingChildren) {
            return null;
        }

        return selectedChildCategory ? selectedParentCategory.id : null;
    });
    let backButtonLabel = $derived.by(() => {
        if (!isBrowsingChildren) {
            return '';
        }

        return selectedChildCategory
            ? t('catalog.back_to_parent', '← :name', { name: selectedParentCategory.name })
            : t('catalog.back', '← Назад');
    });

    function filterByCategory(categoryId) {
        router.get(page.props.routes.catalog, {
            category: categoryId || undefined,
            search: search || undefined,
        }, { preserveState: true });
    }

    function handleSearch(e) {
        e.preventDefault();
        router.get(page.props.routes.catalog, {
            category: filters.category || undefined,
            search: search || undefined,
        }, { preserveState: true });
    }

    function addToCart(productId) {
        router.post(page.props.routes.cart_store, {
            product_id: productId,
            quantity: 1,
        }, { preserveScroll: true });
    }

    function categoryButtonClass(isActive) {
        return `shrink-0 rounded-full px-4 py-2 text-sm font-medium transition-all ${
            isActive
                ? 'bg-stone-900 text-white'
                : 'border border-stone-200 bg-white text-stone-600 hover:border-stone-300 hover:text-stone-900'
        }`;
    }
</script>

{#snippet children()}
<div>
    <!-- Search bar -->
    <form onsubmit={handleSearch} class="mb-6">
        <div class="relative">
            <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <input
                type="text"
                bind:value={search}
                placeholder={t('catalog.search_placeholder', 'Поиск товаров...')}
                class="block w-full rounded-xl border border-stone-200 bg-white py-3 pl-11 pr-24 text-sm text-stone-900 placeholder-stone-400 transition-colors focus:border-stone-300 focus:outline-none focus:ring-2 focus:ring-stone-900/5"
            />
            <button
                type="submit"
                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg bg-stone-900 px-4 py-1.5 text-xs font-medium text-white transition-colors hover:bg-stone-800"
            >
                {t('common.buttons.search', 'Найти')}
            </button>
        </div>
    </form>

    <!-- Category pills (horizontal scroll on mobile) -->
    {#if categories.length > 0}
        <div class="mb-8 flex gap-2 overflow-x-auto pb-2 scrollbar-none">
            {#if isBrowsingChildren}
                <button
                    onclick={() => filterByCategory(backCategoryId)}
                    class="shrink-0 rounded-full border border-stone-200 bg-stone-100 px-4 py-2 text-sm font-medium text-stone-700 transition-all hover:border-stone-300 hover:bg-stone-200/70 hover:text-stone-900"
                >
                    {backButtonLabel}
                </button>
            {:else}
                <button
                    onclick={() => filterByCategory(null)}
                    class={categoryButtonClass(selectedCategoryId === null)}
                >
                    {t('catalog.all_categories', 'Все')}
                </button>
            {/if}

            {#each visibleCategories as cat}
                <button
                    onclick={() => filterByCategory(cat.id)}
                    class={categoryButtonClass(selectedCategoryId === cat.id)}
                >
                    {cat.name}
                </button>
            {/each}
        </div>
    {/if}

    <!-- Products grid -->
    {#if products.data.length === 0}
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mb-4 h-12 w-12 text-stone-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <p class="text-sm text-stone-500">{t('catalog.empty', 'Товары не найдены')}</p>
        </div>
    {:else}
        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4">
            {#each products.data as product}
                <div class="group overflow-hidden rounded-2xl border border-stone-200 bg-white transition-all duration-200 hover:border-stone-300 hover:shadow-md">
                    <a href={product.url} class="block">
                        {#if product.image}
                            <div class="aspect-square overflow-hidden bg-stone-100">
                                <img
                                    src={product.image}
                                    alt={product.name}
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                />
                            </div>
                        {:else}
                            <div class="flex aspect-square items-center justify-center bg-stone-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-stone-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 001.5-1.5V5.25a1.5 1.5 0 00-1.5-1.5H3.75a1.5 1.5 0 00-1.5 1.5v14.25a1.5 1.5 0 001.5 1.5z" />
                                </svg>
                            </div>
                        {/if}
                    </a>
                    <div class="p-3 sm:p-4">
                        <a href={product.url} class="block text-sm font-medium text-stone-900 transition-colors hover:text-stone-600 line-clamp-2">
                            {product.name}
                        </a>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-base font-semibold text-stone-900">{product.bonus_price}</span>
                            <span class="text-xs text-stone-400">{t('common.units.bonuses', 'бонусов')}</span>
                        </div>
                        <div class="mt-1 text-xs text-stone-400">
                            {product.weight_grams} {t('common.units.grams_short', 'г')}
                        </div>
                        <div class="mt-1 text-xs {product.stock_quantity > 0 ? 'text-emerald-600' : 'text-red-500'}">
                            {product.stock_quantity > 0
                                ? t('catalog.available', 'В наличии')
                                : t('catalog.out_of_stock', 'Нет в наличии')}
                        </div>
                        <button
                            onclick={() => addToCart(product.id)}
                            disabled={product.stock_quantity <= 0}
                            class="mt-3 w-full rounded-xl bg-stone-900 px-3 py-2.5 text-xs font-medium text-white transition-all hover:bg-stone-800 active:scale-[0.98] disabled:bg-stone-200 disabled:text-stone-400"
                        >
                            {t('common.buttons.add_to_cart', 'В корзину')}
                        </button>
                    </div>
                </div>
            {/each}
        </div>

        <!-- Pagination -->
        {#if products.links?.length > 3}
            <nav class="mt-8 flex justify-center gap-1">
                {#each products.links as link}
                    {#if link.url}
                        <a
                            href={link.url}
                            class="rounded-lg px-3 py-2 text-sm font-medium transition-colors {link.active
                                ? 'bg-stone-900 text-white'
                                : 'text-stone-600 hover:bg-stone-100 hover:text-stone-900'}"
                        >
                            {@html link.label}
                        </a>
                    {:else}
                        <span class="px-3 py-2 text-sm text-stone-300">
                            {@html link.label}
                        </span>
                    {/if}
                {/each}
            </nav>
        {/if}
    {/if}
</div>
{/snippet}

<Layout {children} />
