<script>
    import { router } from '@inertiajs/svelte';
    import Layout from './Layout.svelte';
    import { useStorefrontTranslations } from './i18n.js';

    let { items, totalBonuses, totalWeightGrams } = $props();
    const { t } = useStorefrontTranslations();

    function updateQuantity(productId, quantity) {
        router.patch(`/storefront/cart/${productId}`, { quantity }, { preserveScroll: true });
    }

    function removeItem(productId) {
        router.delete(`/storefront/cart/${productId}`, { preserveScroll: true });
    }
</script>

{#snippet children()}
<div>
    <h1 class="mb-6 text-2xl font-semibold tracking-tight text-stone-900">{t('cart.title', 'Корзина')}</h1>

    {#if items.length === 0}
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mb-4 h-12 w-12 text-stone-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
            <p class="mb-1 text-sm font-medium text-stone-700">{t('cart.empty_title', 'Корзина пуста')}</p>
            <p class="mb-4 text-xs text-stone-400">{t('cart.empty_description', 'Добавьте товары из каталога')}</p>
            <a href="/storefront" class="rounded-xl bg-stone-900 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-stone-800">
                {t('cart.go_to_catalog', 'Перейти в каталог')}
            </a>
        </div>
    {:else}
        <div class="flex flex-col gap-6 lg:flex-row lg:gap-8">
            <!-- Items list -->
            <div class="flex-1 space-y-3">
                {#each items as item}
                    <div class="flex items-center gap-3 rounded-2xl border border-stone-200 bg-white p-3 sm:gap-4 sm:p-4">
                        {#if item.image}
                            <img src="/storage/{item.image}" alt={item.name} class="h-16 w-16 shrink-0 rounded-xl object-cover sm:h-20 sm:w-20" />
                        {:else}
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-stone-100 sm:h-20 sm:w-20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-stone-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 001.5-1.5V5.25a1.5 1.5 0 00-1.5-1.5H3.75a1.5 1.5 0 00-1.5 1.5v14.25a1.5 1.5 0 001.5 1.5z" />
                                </svg>
                            </div>
                        {/if}

                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-sm font-medium text-stone-900">{item.name}</h3>
                            <p class="mt-0.5 text-xs text-stone-400">{item.bonus_price} {t('common.units.bonus_short', 'бон.')} · {item.weight_grams} {t('common.units.grams_short', 'г')}</p>
                        </div>

                        <div class="flex items-center overflow-hidden rounded-lg border border-stone-200">
                            <button
                                onclick={() => updateQuantity(item.product_id, item.quantity - 1)}
                                class="px-2.5 py-1.5 text-xs text-stone-500 transition-colors hover:bg-stone-50"
                            >
                                −
                            </button>
                            <span class="min-w-[2rem] text-center text-xs font-medium text-stone-900">{item.quantity}</span>
                            <button
                                onclick={() => updateQuantity(item.product_id, Math.min(item.stock_quantity, item.quantity + 1))}
                                class="px-2.5 py-1.5 text-xs text-stone-500 transition-colors hover:bg-stone-50"
                            >
                                +
                            </button>
                        </div>

                        <div class="hidden w-20 text-right text-sm font-medium text-stone-900 sm:block">
                            {item.line_total_bonus} {t('common.units.bonus_short', 'бон.')}
                        </div>

                        <button
                            onclick={() => removeItem(item.product_id)}
                            class="rounded-lg p-1.5 text-stone-300 transition-colors hover:bg-red-50 hover:text-red-500"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                {/each}
            </div>

            <!-- Summary - sticky on mobile -->
            <div class="w-full lg:w-72">
                <div class="sticky top-20 rounded-2xl border border-stone-200 bg-white p-5">
                    <h2 class="mb-4 text-sm font-semibold text-stone-900">{t('cart.summary', 'Итого')}</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-stone-500">{t('cart.weight', 'Вес')}</span>
                            <span class="font-medium text-stone-700">{totalWeightGrams} {t('common.units.grams_short', 'г')}</span>
                        </div>
                        <div class="flex justify-between border-t border-stone-100 pt-3">
                            <span class="font-medium text-stone-900">{t('cart.total', 'Сумма')}</span>
                            <span class="text-lg font-bold text-stone-900">{totalBonuses} {t('common.units.bonus_short', 'бон.')}</span>
                        </div>
                    </div>
                    <a
                        href="/storefront/checkout"
                        class="mt-5 block rounded-xl bg-stone-900 px-4 py-3 text-center text-sm font-medium text-white transition-all hover:bg-stone-800 active:scale-[0.98]"
                    >
                        {t('cart.checkout', 'Оформить заказ')}
                    </a>
                </div>
            </div>
        </div>
    {/if}
</div>
{/snippet}

<Layout {children} />
