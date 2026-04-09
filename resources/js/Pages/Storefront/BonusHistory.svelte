<script>
    import Layout from './Layout.svelte';

    let { transactions } = $props();

    const txTypeLabels = {
        accrual: 'Начисление',
        reserve: 'Резервирование',
        write_off: 'Списание',
        reserve_return: 'Возврат резерва',
        manual_debit: 'Ручное списание',
    };

    const txTypeColors = {
        accrual: 'bg-green-100 text-green-800',
        reserve: 'bg-yellow-100 text-yellow-800',
        write_off: 'bg-red-100 text-red-800',
        reserve_return: 'bg-blue-100 text-blue-800',
        manual_debit: 'bg-red-100 text-red-800',
    };
</script>

{#snippet children()}
<div>
    <h1 class="mb-6 text-2xl font-bold text-gray-900">История бонусов</h1>

    {#if transactions.data.length === 0}
        <p class="text-gray-500">Операций пока нет.</p>
    {:else}
        <div class="overflow-hidden rounded-lg border bg-white">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Дата</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Тип</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Сумма</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Баланс</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Резерв</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Комментарий</th>
                    </tr>
                </thead>
                <tbody>
                    {#each transactions.data as tx}
                        <tr class="border-t">
                            <td class="px-4 py-3 text-gray-600">{tx.created_at}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-medium {txTypeColors[tx.type] ?? 'bg-gray-100 text-gray-800'}">
                                    {txTypeLabels[tx.type] ?? tx.type}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-medium">{tx.amount}</td>
                            <td class="px-4 py-3 text-right" class:text-green-600={tx.balance_delta > 0} class:text-red-600={tx.balance_delta < 0}>
                                {tx.balance_delta > 0 ? '+' : ''}{tx.balance_delta}
                            </td>
                            <td class="px-4 py-3 text-right" class:text-yellow-600={tx.reserved_delta > 0} class:text-blue-600={tx.reserved_delta < 0}>
                                {tx.reserved_delta > 0 ? '+' : ''}{tx.reserved_delta}
                            </td>
                            <td class="px-4 py-3 text-gray-600">{tx.comment ?? ''}</td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        {#if transactions.links?.length > 3}
            <nav class="mt-6 flex justify-center gap-1">
                {#each transactions.links as link}
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
