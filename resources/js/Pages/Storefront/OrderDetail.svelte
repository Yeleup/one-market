<script>
    import Layout from './Layout.svelte';

    let { order } = $props();

    const statusLabels = {
        new: 'Новый',
        processing: 'В обработке',
        delivered: 'Доставлен',
        cancelled: 'Отменён',
    };

    const statusColors = {
        new: 'bg-sky-50 text-sky-700',
        processing: 'bg-amber-50 text-amber-700',
        delivered: 'bg-emerald-50 text-emerald-700',
        cancelled: 'bg-red-50 text-red-700',
    };
</script>

{#snippet children()}
<div>
    <a href="/storefront/orders" class="mb-6 inline-flex items-center gap-1.5 text-sm text-stone-500 transition-colors hover:text-stone-900">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
        Назад к заказам
    </a>

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold tracking-tight text-stone-900">Заказ #{order.id}</h1>
        <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {statusColors[order.status]}">
            {statusLabels[order.status]}
        </span>
    </div>

    <div class="flex flex-col gap-6 lg:flex-row lg:gap-8">
        <!-- Order items -->
        <div class="flex-1 space-y-4">
            <div class="rounded-2xl border border-stone-200 bg-white p-5">
                <h2 class="mb-4 text-sm font-semibold text-stone-900">Товары</h2>
                <div class="space-y-3">
                    {#each order.items as item}
                        <div class="flex items-center gap-3 border-b border-stone-100 pb-3 last:border-0 last:pb-0">
                            {#if item.product_image}
                                <img src="/storage/{item.product_image}" alt={item.product_name} class="h-12 w-12 shrink-0 rounded-xl object-cover" />
                            {:else}
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-stone-100 text-xs text-stone-300">—</div>
                            {/if}
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-stone-900">{item.product_name}</p>
                                <p class="text-xs text-stone-400">{item.price_bonus} бон. × {item.quantity}</p>
                            </div>
                            <span class="shrink-0 text-sm font-medium text-stone-700">{item.line_total_bonus} бон.</span>
                        </div>
                    {/each}
                </div>
            </div>

            <!-- Status history -->
            {#if order.status_histories.length > 0}
                <div class="rounded-2xl border border-stone-200 bg-white p-5">
                    <h2 class="mb-4 text-sm font-semibold text-stone-900">История статусов</h2>
                    <div class="space-y-3">
                        {#each order.status_histories as history}
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="text-xs text-stone-400">{history.created_at}</span>
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {statusColors[history.to_status]}">
                                    {statusLabels[history.to_status]}
                                </span>
                                {#if history.comment}
                                    <span class="text-xs text-stone-500">— {history.comment}</span>
                                {/if}
                            </div>
                        {/each}
                    </div>
                </div>
            {/if}
        </div>

        <!-- Summary -->
        <div class="w-full lg:w-72">
            <div class="sticky top-20 rounded-2xl border border-stone-200 bg-white p-5">
                <h2 class="mb-4 text-sm font-semibold text-stone-900">Информация</h2>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-stone-500">Дата</span>
                        <span class="font-medium text-stone-700">{order.placed_at}</span>
                    </div>
                    {#if order.institution}
                        <div class="flex justify-between">
                            <span class="text-stone-500">Учреждение</span>
                            <span class="font-medium text-stone-700">{order.institution.name}</span>
                        </div>
                    {/if}
                    <div class="flex justify-between">
                        <span class="text-stone-500">Вес</span>
                        <span class="font-medium text-stone-700">{order.total_weight_grams} г</span>
                    </div>
                    <div class="flex justify-between border-t border-stone-100 pt-3">
                        <span class="font-medium text-stone-900">Итого</span>
                        <span class="text-lg font-bold text-stone-900">{order.total_bonus} бон.</span>
                    </div>
                    {#if order.delivered_at}
                        <div class="flex justify-between text-xs">
                            <span class="text-stone-400">Доставлен</span>
                            <span class="text-emerald-600">{order.delivered_at}</span>
                        </div>
                    {/if}
                    {#if order.cancelled_at}
                        <div class="flex justify-between text-xs">
                            <span class="text-stone-400">Отменён</span>
                            <span class="text-red-500">{order.cancelled_at}</span>
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
</div>
{/snippet}

<Layout {children} />
