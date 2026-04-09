<script>
    import { useForm } from '@inertiajs/svelte';
    import Layout from './Layout.svelte';
    import { useStorefrontTranslations } from './i18n.js';

    let { items, totalBonuses, totalWeightGrams, availableBonuses, defaultInstitutionId, institutions } = $props();
    const { t } = useStorefrontTranslations();

    const form = useForm({
        institution_id: defaultInstitutionId ?? '',
    });

    let selectedInstitution = $derived(
        institutions.find(i => i.id == form.institution_id)
    );

    let weightExceeded = $derived(
        selectedInstitution && totalWeightGrams > selectedInstitution.max_weight_grams
    );

    let bonusInsufficient = $derived(totalBonuses > availableBonuses);

    function submit(e) {
        e.preventDefault();
        form.post('/storefront/checkout');
    }
</script>

{#snippet children()}
<div>
    <h1 class="mb-6 text-2xl font-semibold tracking-tight text-stone-900">{t('checkout.title', 'Оформление заказа')}</h1>

    {#if items.length === 0}
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <p class="mb-2 text-sm text-stone-500">{t('checkout.empty', 'Корзина пуста')}</p>
            <a href="/storefront" class="text-sm font-medium text-stone-900 underline decoration-stone-300 underline-offset-4 hover:decoration-stone-900">
                {t('checkout.go_to_catalog', 'Перейти в каталог')}
            </a>
        </div>
    {:else}
        <div class="flex flex-col gap-6 lg:flex-row lg:gap-8">
            <!-- Order items -->
            <div class="flex-1">
                <div class="rounded-2xl border border-stone-200 bg-white p-5">
                    <h2 class="mb-4 text-sm font-semibold text-stone-900">{t('checkout.items', 'Товары')}</h2>
                    <div class="space-y-3">
                        {#each items as item}
                            <div class="flex items-center justify-between border-b border-stone-100 pb-3 last:border-0 last:pb-0">
                                <div>
                                    <span class="text-sm font-medium text-stone-900">{item.name}</span>
                                    <span class="ml-2 text-xs text-stone-400">× {item.quantity}</span>
                                </div>
                                <span class="text-sm font-medium text-stone-700">{item.line_total_bonus} {t('common.units.bonus_short', 'бон.')}</span>
                            </div>
                        {/each}
                    </div>
                </div>
            </div>

            <!-- Summary & form -->
            <div class="w-full space-y-4 lg:w-80">
                <div class="rounded-2xl border border-stone-200 bg-white p-5">
                    <h2 class="mb-4 text-sm font-semibold text-stone-900">{t('checkout.summary', 'Итого')}</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-stone-500">{t('checkout.cost', 'Стоимость')}</span>
                            <span class="font-medium text-stone-700">{totalBonuses} {t('common.units.bonuses', 'бонусов')}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-stone-500">{t('checkout.weight', 'Вес')}</span>
                            <span class="font-medium text-stone-700">{totalWeightGrams} {t('common.units.grams_short', 'г')}</span>
                        </div>
                        <div class="flex justify-between border-t border-stone-100 pt-2">
                            <span class="text-stone-500">{t('checkout.available', 'Доступно')}</span>
                            <span class="font-medium {bonusInsufficient ? 'text-red-600' : 'text-emerald-600'}">
                                {availableBonuses} {t('common.units.bonuses', 'бонусов')}
                            </span>
                        </div>
                    </div>

                    {#if bonusInsufficient}
                        <div class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-xs text-red-700">
                            {t('checkout.insufficient_bonus', 'Недостаточно бонусов для оформления заказа')}
                        </div>
                    {/if}
                    {#if weightExceeded}
                        <div class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-xs text-red-700">
                            {t('checkout.weight_exceeded', 'Вес (:weight г) превышает лимит (:limit г)', {
                                weight: totalWeightGrams,
                                limit: selectedInstitution.max_weight_grams,
                            })}
                        </div>
                    {/if}
                </div>

                <form onsubmit={submit} class="rounded-2xl border border-stone-200 bg-white p-5">
                    <label for="checkout-institution" class="mb-1.5 block text-sm font-medium text-stone-700">{t('checkout.institution', 'Учреждение')}</label>
                    <select
                        id="checkout-institution"
                        bind:value={form.institution_id}
                        class="block w-full appearance-none rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 transition-colors focus:border-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-stone-900/5"
                    >
                        <option value="">{t('checkout.institution_placeholder', '— Выберите —')}</option>
                        {#each institutions as inst}
                            <option value={inst.id}>{inst.name}</option>
                        {/each}
                    </select>
                    {#if form.errors.institution_id}
                        <p class="mt-1.5 text-xs text-red-600">{form.errors.institution_id}</p>
                    {/if}
                    {#if form.errors.cart}
                        <p class="mt-1.5 text-xs text-red-600">{form.errors.cart}</p>
                    {/if}

                    <button
                        type="submit"
                        disabled={form.processing || bonusInsufficient || weightExceeded || !form.institution_id}
                        class="mt-4 w-full rounded-xl bg-stone-900 px-4 py-3 text-sm font-medium text-white transition-all hover:bg-stone-800 active:scale-[0.98] disabled:bg-stone-200 disabled:text-stone-400"
                    >
                        {form.processing ? t('checkout.submitting', 'Оформление...') : t('checkout.submit', 'Подтвердить заказ')}
                    </button>
                </form>
            </div>
        </div>
    {/if}
</div>
{/snippet}

<Layout {children} />
