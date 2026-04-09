<script>
    import Layout from './Layout.svelte';

    let { client, recentOrders, recentTransactions } = $props();

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

    const txTypeLabels = {
        accrual: 'Начисление',
        reserve: 'Резервирование',
        write_off: 'Списание',
        reserve_return: 'Возврат резерва',
        manual_debit: 'Ручное списание',
    };
</script>

{#snippet children()}
<div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Личный кабинет</h1>

    <!-- Bonus cards -->
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg border bg-white p-6">
            <p class="text-sm text-gray-500">Бонусный баланс</p>
            <p class="mt-1 text-3xl font-bold text-gray-900">{client.bonus_balance}</p>
        </div>
        <div class="rounded-lg border bg-white p-6">
            <p class="text-sm text-gray-500">Зарезервировано</p>
            <p class="mt-1 text-3xl font-bold text-yellow-600">{client.bonus_reserved}</p>
        </div>
        <div class="rounded-lg border bg-white p-6">
            <p class="text-sm text-gray-500">Доступно</p>
            <p class="mt-1 text-3xl font-bold text-green-600">{client.available_bonuses}</p>
        </div>
    </div>

    <!-- Recent orders -->
    <div class="mb-8">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Последние заказы</h2>
            <a href="/storefront/orders" class="text-sm text-blue-600 hover:underline">Все заказы</a>
        </div>
        {#if recentOrders.length === 0}
            <p class="text-sm text-gray-500">Заказов пока нет.</p>
        {:else}
            <div class="overflow-hidden rounded-lg border bg-white">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">#</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Дата</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Статус</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each recentOrders as order}
                            <tr class="border-t">
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
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        {/if}
    </div>

    <!-- Recent transactions -->
    <div>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Последние операции</h2>
            <a href="/storefront/bonuses" class="text-sm text-blue-600 hover:underline">Вся история</a>
        </div>
        {#if recentTransactions.length === 0}
            <p class="text-sm text-gray-500">Операций пока нет.</p>
        {:else}
            <div class="overflow-hidden rounded-lg border bg-white">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Дата</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Тип</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Сумма</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Комментарий</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each recentTransactions as tx}
                            <tr class="border-t">
                                <td class="px-4 py-3 text-gray-600">{tx.created_at}</td>
                                <td class="px-4 py-3">{txTypeLabels[tx.type] ?? tx.type}</td>
                                <td class="px-4 py-3 text-right font-medium">{tx.amount}</td>
                                <td class="px-4 py-3 text-gray-600">{tx.comment ?? ''}</td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        {/if}
    </div>
</div>
{/snippet}

<Layout {children} />
