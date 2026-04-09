<script>
    import { router } from '@inertiajs/svelte';
    import Layout from './Layout.svelte';
    import { useStorefrontTranslations } from './i18n.js';

    let { product } = $props();
    let quantity = $state(1);
    const { t } = useStorefrontTranslations();

    function addToCart() {
        router.post('/storefront/cart', {
            product_id: product.id,
            quantity,
        });
    }
</script>

{#snippet children()}
<div>
    <a href="/storefront" class="mb-6 inline-flex items-center gap-1.5 text-sm text-stone-500 transition-colors hover:text-stone-900">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
        {t('product.back', 'Назад в каталог')}
    </a>

    {#if product.category}
        <p class="mb-2 text-xs font-medium uppercase tracking-wider text-stone-400">{product.category.name}</p>
    {/if}

    <div class="flex flex-col gap-8 lg:flex-row lg:gap-12">
        <!-- Images -->
        <div class="w-full lg:w-1/2">
            {#if product.images.length > 0}
                <div class="space-y-3">
                    {#each product.images as img, i}
                        <div class="overflow-hidden rounded-2xl bg-stone-100">
                            <img
                                src="/storage/{img.image}"
                                alt={t('product.image_alt', ':name — фото :number', { name: product.name, number: i + 1 })}
                                class="w-full object-cover"
                            />
                        </div>
                    {/each}
                </div>
            {:else}
                <div class="flex aspect-square items-center justify-center rounded-2xl bg-stone-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-stone-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 001.5-1.5V5.25a1.5 1.5 0 00-1.5-1.5H3.75a1.5 1.5 0 00-1.5 1.5v14.25a1.5 1.5 0 001.5 1.5z" />
                    </svg>
                </div>
            {/if}
        </div>

        <!-- Info -->
        <div class="w-full lg:w-1/2">
            <h1 class="text-2xl font-semibold tracking-tight text-stone-900 sm:text-3xl">{product.name}</h1>

            <div class="mt-6 space-y-4">
                <div>
                    <span class="text-3xl font-bold text-stone-900">{product.bonus_price}</span>
                    <span class="ml-1.5 text-sm text-stone-400">{t('product.price_suffix', 'бонусов')}</span>
                </div>

                <div class="flex items-center gap-4 text-sm">
                    <span class="text-stone-500">{t('product.weight', 'Вес: :weight г', { weight: product.weight_grams })}</span>
                    <span class="text-stone-300">·</span>
                    <span class="{product.stock_quantity > 0 ? 'text-emerald-600' : 'text-red-500'}">
                        {product.stock_quantity > 0
                            ? t('product.in_stock_count', 'В наличии: :count шт.', { count: product.stock_quantity })
                            : t('product.out_of_stock', 'Нет в наличии')}
                    </span>
                </div>
            </div>

            {#if product.stock_quantity > 0}
                <div class="mt-8 flex items-center gap-4">
                    <!-- Quantity selector -->
                    <div class="flex items-center overflow-hidden rounded-xl border border-stone-200 bg-white">
                        <button
                            onclick={() => quantity = Math.max(1, quantity - 1)}
                            class="px-4 py-3 text-stone-500 transition-colors hover:bg-stone-50 hover:text-stone-900"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                            </svg>
                        </button>
                        <span class="min-w-[3rem] text-center text-sm font-medium text-stone-900">{quantity}</span>
                        <button
                            onclick={() => quantity = Math.min(product.stock_quantity, quantity + 1)}
                            class="px-4 py-3 text-stone-500 transition-colors hover:bg-stone-50 hover:text-stone-900"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </button>
                    </div>

                    <button
                        onclick={addToCart}
                        class="flex-1 rounded-xl bg-stone-900 px-6 py-3 text-sm font-medium text-white transition-all hover:bg-stone-800 active:scale-[0.98]"
                    >
                        {t('common.buttons.add_to_cart', 'В корзину')}
                    </button>
                </div>
            {/if}
        </div>
    </div>
</div>
{/snippet}

<Layout {children} />
