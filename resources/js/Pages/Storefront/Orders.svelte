<script>
    import Layout from './Layout.svelte';

    let { orders } = $props();

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
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Мои заказы</h1>

    {#if orders.data.length === 0}
        <p class="text-gray-500">У вас пока нет заказов.</p>
    {:else}
        <div class="overflow-hidden rounded-lg border bg-white">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">#</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Дата</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Статус</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Сумма</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Вес</th>
                    </tr>
                </thead>
                <tbody>
                    {#each orders.data as order}
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="/storefront/orders/{order.id}" class="text-blue-600 hover:underline">{order.id}</a>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{order.placed_at}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-medium {statusColors[order.status]}">
                                    {statusLabels[order.status]}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-medium">{order.total_bonus} бон.</td>
                            <td class="px-4 py-3 text-right text-gray-600">{order.total_weight_grams} г</td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        {#if orders.links?.length > 3}
            <nav class="mt-6 flex justify-center gap-1">
                {#each orders.links as link}
                    {#if link.url}
                        <a
                            href={link.url}
                            class="rounded-md px-3 py-2 text-sm"
                            class:bg-blue-600={link.active}
                            class:text-white={link.active}
                            class:bg-white={!link.active}
                            class:text-gray-700={!link.active}
                        >
                            {@html link.label}
                        </a>
                    {:else}
                        <span class="px-3 py-2 text-sm text-gray-400">{@html link.label}</span>
                    {/if}
                {/each}
            </nav>
        {/if}
    {/if}
</div>
{/snippet}

<Layout {children} />
