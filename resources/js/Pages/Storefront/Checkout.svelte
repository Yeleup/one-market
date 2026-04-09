<script>
    import { useForm } from '@inertiajs/svelte';
    import Layout from './Layout.svelte';

    let { items, totalBonuses, totalWeightGrams, availableBonuses, defaultInstitutionId, institutions } = $props();

    const form = useForm({
        institution_id: defaultInstitutionId ?? '',
    });

    let selectedInstitution = $derived(
        institutions.find(i => i.id == $form.institution_id)
    );

    let weightExceeded = $derived(
        selectedInstitution && totalWeightGrams > selectedInstitution.max_weight_grams
    );

    let bonusInsufficient = $derived(totalBonuses > availableBonuses);

    function submit(e) {
        e.preventDefault();
        $form.post('/storefront/checkout');
    }
</script>

{#snippet children()}
<div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Оформление заказа</h1>

    {#if items.length === 0}
        <p class="text-gray-500">Корзина пуста. <a href="/storefront" class="text-blue-600 hover:underline">Перейти в каталог</a></p>
    {:else}
        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Order items -->
            <div class="lg:col-span-2">
                <div class="rounded-lg border bg-white p-4">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Товары</h2>
                    <div class="space-y-3">
                        {#each items as item}
                            <div class="flex items-center justify-between border-b pb-3 last:border-0 last:pb-0">
                                <div>
                                    <span class="font-medium text-gray-900">{item.name}</span>
                                    <span class="ml-2 text-sm text-gray-500">&times; {item.quantity}</span>
                                </div>
                                <span class="font-medium text-gray-900">{item.line_total_bonus} бон.</span>
                            </div>
                        {/each}
                    </div>
                </div>
            </div>

            <!-- Summary & Form -->
            <div class="space-y-4">
                <div class="rounded-lg border bg-white p-4">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">Итого</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Стоимость:</span>
                            <span class="font-medium">{totalBonuses} бонусов</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Вес:</span>
                            <span class="font-medium">{totalWeightGrams} г</span>
                        </div>
                        <div class="flex justify-between border-t pt-2">
                            <span class="text-gray-600">Доступно бонусов:</span>
                            <span class="font-medium" class:text-red-600={bonusInsufficient}>
                                {availableBonuses}
                            </span>
                        </div>
                    </div>

                    {#if bonusInsufficient}
                        <p class="mt-3 text-sm text-red-600">Недостаточно бонусов для оформления заказа.</p>
                    {/if}

                    {#if weightExceeded}
                        <p class="mt-3 text-sm text-red-600">
                            Вес корзины ({totalWeightGrams} г) превышает лимит учреждения ({selectedInstitution.max_weight_grams} г).
                        </p>
                    {/if}
                </div>

                <form onsubmit={submit} class="rounded-lg border bg-white p-4">
                    <label for="institution_id" class="block text-sm font-medium text-gray-700">Учреждение</label>
                    <select
                        id="institution_id"
                        bind:value={$form.institution_id}
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="">— Выберите учреждение —</option>
                        {#each institutions as inst}
                            <option value={inst.id}>{inst.name}</option>
                        {/each}
                    </select>
                    {#if $form.errors.institution_id}
                        <p class="mt-1 text-sm text-red-600">{$form.errors.institution_id}</p>
                    {/if}
                    {#if $form.errors.cart}
                        <p class="mt-1 text-sm text-red-600">{$form.errors.cart}</p>
                    {/if}

                    <button
                        type="submit"
                        disabled={$form.processing || bonusInsufficient || weightExceeded || !$form.institution_id}
                        class="mt-4 w-full rounded-md bg-blue-600 px-4 py-3 text-white hover:bg-blue-700 disabled:bg-gray-300 disabled:text-gray-500"
                    >
                        {$form.processing ? 'Оформление...' : 'Подтвердить заказ'}
                    </button>
                </form>
            </div>
        </div>
    {/if}
</div>
{/snippet}

<Layout {children} />
