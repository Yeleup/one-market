<script>
    import { router } from '@inertiajs/svelte';
    import Layout from './Layout.svelte';

    let { items, totalBonuses, totalWeightGrams } = $props();

    function updateQuantity(productId, quantity) {
        router.patch(`/storefront/cart/${productId}`, { quantity }, { preserveScroll: true });
    }

    function removeItem(productId) {
        router.delete(`/storefront/cart/${productId}`, { preserveScroll: true });
    }
</script>

{#snippet children()}
<div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Корзина</h1>

    {#if items.length === 0}
        <div class="text-center">
            <p class="text-gray-500">Корзина пуста</p>
            <a href="/storefront" class="mt-4 inline-block text-blue-600 hover:underline">Перейти в каталог</a>
        </div>
    {:else}
        <div class="space-y-4">
            {#each items as item}
                <div class="flex items-center gap-4 rounded-lg border bg-white p-4">
                    {#if item.image}
                        <img src="/storage/{item.image}" alt={item.name} class="h-20 w-20 rounded-md object-cover" />
                    {:else}
                        <div class="flex h-20 w-20 items-center justify-center rounded-md bg-gray-100 text-xs text-gray-400">
                            Нет фото
                        </div>
                    {/if}

                    <div class="flex-1">
                        <h3 class="font-medium text-gray-900">{item.name}</h3>
                        <p class="text-sm text-gray-500">{item.bonus_price} бонусов за шт. &middot; {item.weight_grams} г</p>
                    </div>

                    <div class="flex items-center rounded-md border">
                        <button
                            onclick={() => updateQuantity(item.product_id, item.quantity - 1)}
                            class="px-3 py-1 text-gray-600 hover:bg-gray-100"
                        >
                            -
                        </button>
                        <span class="px-3 py-1 text-sm">{item.quantity}</span>
                        <button
                            onclick={() => updateQuantity(item.product_id, Math.min(item.stock_quantity, item.quantity + 1))}
                            class="px-3 py-1 text-gray-600 hover:bg-gray-100"
                        >
                            +
                        </button>
                    </div>

                    <div class="w-24 text-right font-medium text-gray-900">
                        {item.line_total_bonus} бон.
                    </div>

                    <button onclick={() => removeItem(item.product_id)} class="text-red-500 hover:text-red-700">
                        &times;
                    </button>
                </div>
            {/each}
        </div>

        <div class="mt-6 rounded-lg border bg-white p-6">
            <div class="flex justify-between text-sm text-gray-600">
                <span>Общий вес:</span>
                <span>{totalWeightGrams} г</span>
            </div>
            <div class="mt-2 flex justify-between text-lg font-bold text-gray-900">
                <span>Итого:</span>
                <span>{totalBonuses} бонусов</span>
            </div>
            <a
                href="/storefront/checkout"
                class="mt-4 block rounded-md bg-blue-600 px-4 py-3 text-center text-white hover:bg-blue-700"
            >
                Оформить заказ
            </a>
        </div>
    {/if}
</div>
{/snippet}

<Layout {children} />
