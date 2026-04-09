<script>
    import Layout from './Layout.svelte';
    import { useStorefrontTranslations } from './i18n.js';

    let { transactions } = $props();
    const { t } = useStorefrontTranslations();

    const txTypeColors = {
        accrual: 'bg-emerald-50 text-emerald-700',
        reserve: 'bg-amber-50 text-amber-700',
        write_off: 'bg-red-50 text-red-700',
        reserve_return: 'bg-sky-50 text-sky-700',
        manual_debit: 'bg-red-50 text-red-700',
    };

    function transactionTypeLabel(type) {
        return t(`common.transaction_type.${type}`, type);
    }
</script>

{#snippet children()}
<div>
    <h1 class="mb-6 text-2xl font-semibold tracking-tight text-stone-900">{t('bonus_history.title', 'История бонусов')}</h1>

    {#if transactions.data.length === 0}
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mb-4 h-12 w-12 text-stone-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-stone-500">{t('bonus_history.empty', 'Операций пока нет')}</p>
        </div>
    {:else}
        <!-- Mobile: card layout -->
        <div class="space-y-3 sm:hidden">
            {#each transactions.data as tx}
                <div class="rounded-2xl border border-stone-200 bg-white p-4">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {txTypeColors[tx.type] ?? 'bg-stone-100 text-stone-700'}">
                            {transactionTypeLabel(tx.type)}
                        </span>
                        <span class="text-sm font-medium text-stone-900">{tx.amount}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-stone-400">
                        <span>{tx.created_at}</span>
                        <div class="flex gap-2">
                            <span class="{tx.balance_delta > 0 ? 'text-emerald-600' : tx.balance_delta < 0 ? 'text-red-500' : ''}">
                                {tx.balance_delta > 0 ? '+' : ''}{tx.balance_delta}
                            </span>
                        </div>
                    </div>
                    {#if tx.comment}
                        <p class="mt-1.5 text-xs text-stone-400">{tx.comment}</p>
                    {/if}
                </div>
            {/each}
        </div>

        <!-- Desktop: table layout -->
        <div class="hidden overflow-hidden rounded-2xl border border-stone-200 bg-white sm:block">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-stone-100">
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">{t('bonus_history.date', 'Дата')}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">{t('bonus_history.type', 'Тип')}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-stone-400">{t('bonus_history.amount', 'Сумма')}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-stone-400">{t('bonus_history.balance', 'Баланс')}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-stone-400">{t('bonus_history.reserve', 'Резерв')}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">{t('bonus_history.comment', 'Комментарий')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {#each transactions.data as tx}
                            <tr class="border-t border-stone-50 transition-colors hover:bg-stone-50/50">
                                <td class="px-4 py-3 text-stone-500">{tx.created_at}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {txTypeColors[tx.type] ?? 'bg-stone-100 text-stone-700'}">
                                        {transactionTypeLabel(tx.type)}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-stone-900">{tx.amount}</td>
                                <td class="px-4 py-3 text-right {tx.balance_delta > 0 ? 'text-emerald-600' : tx.balance_delta < 0 ? 'text-red-500' : 'text-stone-400'}">
                                    {tx.balance_delta > 0 ? '+' : ''}{tx.balance_delta}
                                </td>
                                <td class="px-4 py-3 text-right {tx.reserved_delta > 0 ? 'text-amber-600' : tx.reserved_delta < 0 ? 'text-sky-600' : 'text-stone-400'}">
                                    {tx.reserved_delta > 0 ? '+' : ''}{tx.reserved_delta}
                                </td>
                                <td class="px-4 py-3 text-stone-400">{tx.comment ?? ''}</td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        {#if transactions.links?.length > 3}
            <nav class="mt-8 flex justify-center gap-1">
                {#each transactions.links as link}
                    {#if link.url}
                        <a
                            href={link.url}
                            class="rounded-lg px-3 py-2 text-sm font-medium transition-colors {link.active
                                ? 'bg-stone-900 text-white'
                                : 'text-stone-600 hover:bg-stone-100 hover:text-stone-900'}"
                        >
                            {@html link.label}
                        </a>
                    {:else}
                        <span class="px-3 py-2 text-sm text-stone-300">{@html link.label}</span>
                    {/if}
                {/each}
            </nav>
        {/if}
    {/if}
</div>
{/snippet}

<Layout {children} />
