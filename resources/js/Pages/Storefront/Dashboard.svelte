<script>
    import { usePage } from '@inertiajs/svelte';
    import Layout from './Layout.svelte';
    import { useStorefrontTranslations } from './i18n.js';

    let { client, recentOrders, recentTransactions } = $props();
    const page = usePage();
    const { t } = useStorefrontTranslations();

    const statusColors = {
        new: 'bg-sky-50 text-sky-700',
        processing: 'bg-amber-50 text-amber-700',
        delivered: 'bg-emerald-50 text-emerald-700',
        cancelled: 'bg-red-50 text-red-700',
    };

    function orderStatusLabel(status) {
        return t(`common.order_status.${status}`, status);
    }

    function transactionTypeLabel(type) {
        return t(`common.transaction_type.${type}`, type);
    }
</script>

{#snippet children()}
<div>
    <h1 class="mb-6 text-2xl font-semibold tracking-tight text-stone-900">{t('dashboard.title', 'Личный кабинет')}</h1>

    <!-- Stat cards -->
    <div class="mb-8 grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
        <div class="rounded-2xl border border-stone-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-stone-400">{t('dashboard.balance', 'Баланс')}</p>
            <p class="mt-2 text-3xl font-bold tracking-tight text-stone-900">{client.bonus_balance}</p>
            <p class="mt-0.5 text-xs text-stone-400">{t('common.units.bonuses', 'бонусов')}</p>
        </div>
        <div class="rounded-2xl border border-amber-100 bg-amber-50/50 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-amber-600/70">{t('dashboard.reserved', 'Зарезервировано')}</p>
            <p class="mt-2 text-3xl font-bold tracking-tight text-amber-700">{client.bonus_reserved}</p>
            <p class="mt-0.5 text-xs text-amber-500">{t('common.units.bonuses', 'бонусов')}</p>
        </div>
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-5">
            <p class="text-xs font-medium uppercase tracking-wider text-emerald-600/70">{t('dashboard.available', 'Доступно')}</p>
            <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-700">{client.available_bonuses}</p>
            <p class="mt-0.5 text-xs text-emerald-500">{t('common.units.bonuses', 'бонусов')}</p>
        </div>
    </div>

    <!-- Recent orders -->
    <div class="mb-8">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-base font-semibold text-stone-900">{t('dashboard.recent_orders', 'Последние заказы')}</h2>
            <a href={page.props.routes.orders} class="text-xs font-medium text-stone-500 transition-colors hover:text-stone-900">{t('dashboard.all_orders', 'Все заказы →')}</a>
        </div>
        {#if recentOrders.length === 0}
            <div class="rounded-2xl border border-stone-200 bg-white px-4 py-8 text-center text-sm text-stone-400">
                {t('dashboard.no_orders', 'Заказов пока нет')}
            </div>
        {:else}
            <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-stone-100">
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">{t('dashboard.date', 'Дата')}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">{t('dashboard.status', 'Статус')}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-stone-400">{t('dashboard.amount', 'Сумма')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each recentOrders as order}
                                <tr class="border-t border-stone-50 transition-colors hover:bg-stone-50/50">
                                    <td class="px-4 py-3">
                                        <a href={order.url} class="font-medium text-stone-900 hover:underline">{order.id}</a>
                                    </td>
                                    <td class="px-4 py-3 text-stone-500">{order.placed_at}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {statusColors[order.status]}">
                                            {orderStatusLabel(order.status)}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-stone-900">{order.total_bonus} {t('common.units.bonus_short', 'бон.')}</td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            </div>
        {/if}
    </div>

    <!-- Recent transactions -->
    <div>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-base font-semibold text-stone-900">{t('dashboard.recent_transactions', 'Последние операции')}</h2>
            <a href={page.props.routes.bonuses} class="text-xs font-medium text-stone-500 transition-colors hover:text-stone-900">{t('dashboard.all_history', 'Вся история →')}</a>
        </div>
        {#if recentTransactions.length === 0}
            <div class="rounded-2xl border border-stone-200 bg-white px-4 py-8 text-center text-sm text-stone-400">
                {t('dashboard.no_transactions', 'Операций пока нет')}
            </div>
        {:else}
            <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-stone-100">
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">{t('dashboard.date', 'Дата')}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">{t('dashboard.type', 'Тип')}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-stone-400">{t('dashboard.amount', 'Сумма')}</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400 sm:table-cell">{t('dashboard.comment', 'Комментарий')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {#each recentTransactions as tx}
                                <tr class="border-t border-stone-50 transition-colors hover:bg-stone-50/50">
                                    <td class="px-4 py-3 text-stone-500">{tx.created_at}</td>
                                    <td class="px-4 py-3 text-stone-700">{transactionTypeLabel(tx.type)}</td>
                                    <td class="px-4 py-3 text-right font-medium text-stone-900">{tx.amount}</td>
                                    <td class="hidden px-4 py-3 text-stone-400 sm:table-cell">{tx.comment ?? ''}</td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            </div>
        {/if}
    </div>
</div>
{/snippet}

<Layout {children} />
