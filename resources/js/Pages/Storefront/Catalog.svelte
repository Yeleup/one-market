<script>
    import { router } from '@inertiajs/svelte';
    import Layout from './Layout.svelte';

    let { categories, products, filters } = $props();

    let search = $state(filters.search ?? '');

    function filterByCategory(categoryId) {
        router.get('/storefront', {
            category: categoryId || undefined,
            search: search || undefined,
        }, { preserveState: true });
    }

    function handleSearch(e) {
        e.preventDefault();
        router.get('/storefront', {
            category: filters.category || undefined,
            search: search || undefined,
        }, { preserveState: true });
    }

    function addToCart(productId) {
        router.post('/storefront/cart', {
            product_id: productId,
            quantity: 1,
        }, { preserveScroll: true });
    }
</script>

{#snippet children()}
<div class="flex flex-col gap-6 lg:flex-row">
    <!-- Sidebar: Categories -->
    <aside class="w-full shrink-0 lg:w-64">
        <h2 class="mb-3 text-lg font-semibold text-gray-900">Категории</h2>
        <ul class="space-y-1">
            <li>
                <button
                    onclick={() => filterByCategory(null)}
                    class="w-full rounded-md px-3 py-2 text-left text-sm hover:bg-gray-100"
                    class:bg-blue-50={!filters.category}
                    class:text-blue-700={!filters.category}
                >
                    Все товары
                </button>
            </li>
            {#each categories as cat}
                <li>
                    <button
                        onclick={() => filterByCategory(cat.id)}
                        class="w-full rounded-md px-3 py-2 text-left text-sm hover:bg-gray-100"
                        class:bg-blue-50={filters.category === cat.id}
                        class:text-blue-700={filters.category === cat.id}
                    >
                        {cat.name}
                    </button>
                    {#if cat.children?.length > 0}
                        <ul class="ml-4 space-y-1">
                            {#each cat.children as child}
                                <li>
                                    <button
                                        onclick={() => filterByCategory(child.id)}
                                        class="w-full rounded-md px-3 py-2 text-left text-sm hover:bg-gray-100"
                                        class:bg-blue-50={filters.category === child.id}
                                        class:text-blue-700={filters.category === child.id}
                                    >
                                        {child.name}
                                    </button>
                                </li>
                            {/each}
                        </ul>
                    {/if}
                </li>
            {/each}
        </ul>
    </aside>

    <!-- Main content -->
    <div class="flex-1">
        <!-- Search -->
        <form onsubmit={handleSearch} class="mb-6 flex gap-2">
            <input
                type="text"
                bind:value={search}
                placeholder="Поиск товаров..."
                class="flex-1 rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                Найти
            </button>
        </form>

        <!-- Products grid -->
        {#if products.data.length === 0}
            <p class="text-gray-500">Товары не найдены.</p>
        {:else}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {#each products.data as product}
                    <div class="overflow-hidden rounded-lg border bg-white shadow-sm">
                        <a href="/storefront/products/{product.id}">
                            {#if product.image}
                                <img src="/storage/{product.image}" alt={product.name} class="h-48 w-full object-cover" />
                            {:else}
                                <div class="flex h-48 items-center justify-center bg-gray-100 text-gray-400">
                                    Нет фото
                                </div>
                            {/if}
                        </a>
                        <div class="p-4">
                            <a href="/storefront/products/{product.id}" class="block text-sm font-medium text-gray-900 hover:text-blue-600">
                                {product.name}
                            </a>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-lg font-bold text-blue-600">{product.bonus_price} бонусов</span>
                                <span class="text-xs text-gray-500">{product.weight_grams} г</span>
                            </div>
                            <div class="mt-1 text-xs text-gray-500">
                                {product.stock_quantity > 0 ? `В наличии: ${product.stock_quantity}` : 'Нет в наличии'}
                            </div>
                            <button
                                onclick={() => addToCart(product.id)}
                                disabled={product.stock_quantity <= 0}
                                class="mt-3 w-full rounded-md bg-blue-600 px-3 py-2 text-sm text-white hover:bg-blue-700 disabled:bg-gray-300 disabled:text-gray-500"
                            >
                                В корзину
                            </button>
                        </div>
                    </div>
                {/each}
            </div>

            <!-- Pagination -->
            {#if products.links?.length > 3}
                <nav class="mt-6 flex justify-center gap-1">
                    {#each products.links as link}
                        {#if link.url}
                            <a
                                href={link.url}
                                class="rounded-md px-3 py-2 text-sm"
                                class:bg-blue-600={link.active}
                                class:text-white={link.active}
                                class:bg-white={!link.active}
                                class:text-gray-700={!link.active}
                                class:hover:bg-gray-100={!link.active}
                            >
                                {@html link.label}
                            </a>
                        {:else}
                            <span class="px-3 py-2 text-sm text-gray-400">
                                {@html link.label}
                            </span>
                        {/if}
                    {/each}
                </nav>
            {/if}
        {/if}
    </div>
</div>
{/snippet}

<Layout {children} />
