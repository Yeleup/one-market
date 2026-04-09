<script>
    import { router } from '@inertiajs/svelte';
    import Layout from './Layout.svelte';

    let { product } = $props();
    let quantity = $state(1);

    function addToCart() {
        router.post('/storefront/cart', {
            product_id: product.id,
            quantity,
        });
    }
</script>

{#snippet children()}
<div>
    <a href="/storefront" class="mb-4 inline-block text-sm text-blue-600 hover:underline">&larr; Назад в каталог</a>

    {#if product.category}
        <p class="mb-2 text-sm text-gray-500">{product.category.name}</p>
    {/if}

    <div class="flex flex-col gap-8 lg:flex-row">
        <!-- Images -->
        <div class="w-full lg:w-1/2">
            {#if product.images.length > 0}
                <div class="space-y-4">
                    {#each product.images as img}
                        <img src="/storage/{img.image}" alt={product.name} class="w-full rounded-lg object-cover" />
                    {/each}
                </div>
            {:else}
                <div class="flex h-64 items-center justify-center rounded-lg bg-gray-100 text-gray-400">
                    Нет фото
                </div>
            {/if}
        </div>

        <!-- Info -->
        <div class="w-full lg:w-1/2">
            <h1 class="text-2xl font-bold text-gray-900">{product.name}</h1>

            <div class="mt-4 space-y-3">
                <div class="text-3xl font-bold text-blue-600">{product.bonus_price} бонусов</div>
                <div class="text-sm text-gray-600">Вес: {product.weight_grams} г</div>
                <div class="text-sm" class:text-green-600={product.stock_quantity > 0} class:text-red-600={product.stock_quantity <= 0}>
                    {product.stock_quantity > 0 ? `В наличии: ${product.stock_quantity} шт.` : 'Нет в наличии'}
                </div>
            </div>

            {#if product.stock_quantity > 0}
                <div class="mt-6 flex items-center gap-4">
                    <div class="flex items-center rounded-md border">
                        <button
                            onclick={() => quantity = Math.max(1, quantity - 1)}
                            class="px-3 py-2 text-gray-600 hover:bg-gray-100"
                        >
                            -
                        </button>
                        <span class="px-4 py-2 text-sm">{quantity}</span>
                        <button
                            onclick={() => quantity = Math.min(product.stock_quantity, quantity + 1)}
                            class="px-3 py-2 text-gray-600 hover:bg-gray-100"
                        >
                            +
                        </button>
                    </div>
                    <button
                        onclick={addToCart}
                        class="rounded-md bg-blue-600 px-6 py-2 text-white hover:bg-blue-700"
                    >
                        В корзину
                    </button>
                </div>
            {/if}
        </div>
    </div>
</div>
{/snippet}

<Layout {children} />
