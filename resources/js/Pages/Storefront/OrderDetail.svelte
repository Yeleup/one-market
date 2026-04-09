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
        new: 'bg-blue-100 text-blue-800',
        processing: 'bg-yellow-100 text-yellow-800',
        delivered: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    };
</script>

{#snippet children()}
<div>
    <a href="/storefront/orders" class="mb-4 inline-block text-sm text-blue-600 hover:underline">&larr; Назад к заказам</a>

    <div class="mb-6 flex items-center gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Заказ #{order.id}</h1>
        <span class="rounded-full px-3 py-1 text-sm font-medium {statusColors[order.status]}">
            {statusLabels[order.status]}
        </span>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Order items -->
        <div class="lg:col-span-2">
            <div class="rounded-lg border bg-white p-4">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Товары</h2>
                <div class="space-y-3">
                    {#each order.items as item}
                        <div class="flex items-center gap-4 border-b pb-3 last:border-0 last:pb-0">
                            {#if item.product_image}
                                <img src="/storage/{item.product_image}" alt={item.product_name} class="h-12 w-12 rounded-md object-cover" />
                            {:else}
                                <div class="flex h-12 w-12 items-center justify-center rounded-md bg-gray-100 text-xs text-gray-400">-</div>
                            {/if}
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">{item.product_name}</p>
                                <p class="text-sm text-gray-500">{item.price_bonus} бон. &times; {item.quantity}</p>
                            </div>
                            <span class="font-medium text-gray-900">{item.line_total_bonus} бон.</span>
                        </div>
                    {/each}
                </div>
            </div>

            <!-- Status history -->
            {#if order.status_histories.length > 0}
                <div class="mt-4 rounded-lg border bg-white p-4">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900">История статусов</h2>
                    <div class="space-y-3">
                        {#each order.status_histories as history}
                            <div class="flex items-center gap-3 text-sm">
                                <span class="text-gray-500">{history.created_at}</span>
                                <span class="rounded-full px-2 py-1 text-xs font-medium {statusColors[history.to_status]}">
                                    {statusLabels[history.to_status]}
                                </span>
                                {#if history.comment}
                                    <span class="text-gray-600">{history.comment}</span>
                                {/if}
                            </div>
                        {/each}
                    </div>
                </div>
            {/if}
        </div>

        <!-- Summary -->
        <div class="rounded-lg border bg-white p-4">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">Информация</h2>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Дата:</span>
                    <span class="font-medium">{order.placed_at}</span>
                </div>
                {#if order.institution}
                    <div class="flex justify-between">
                        <span class="text-gray-600">Учреждение:</span>
                        <span class="font-medium">{order.institution.name}</span>
                    </div>
                {/if}
                <div class="flex justify-between">
                    <span class="text-gray-600">Общий вес:</span>
                    <span class="font-medium">{order.total_weight_grams} г</span>
                </div>
                <div class="flex justify-between border-t pt-3">
                    <span class="text-gray-900 font-medium">Итого:</span>
                    <span class="text-lg font-bold text-blue-600">{order.total_bonus} бон.</span>
                </div>
                {#if order.delivered_at}
                    <div class="flex justify-between">
                        <span class="text-gray-600">Доставлен:</span>
                        <span class="font-medium">{order.delivered_at}</span>
                    </div>
                {/if}
                {#if order.cancelled_at}
                    <div class="flex justify-between">
                        <span class="text-gray-600">Отменён:</span>
                        <span class="font-medium">{order.cancelled_at}</span>
                    </div>
                {/if}
            </div>
        </div>
    </div>
</div>
{/snippet}

<Layout {children} />
