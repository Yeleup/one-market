<script>
    import { router } from '@inertiajs/svelte';
    import Layout from './Layout.svelte';
    import { useStorefrontTranslations } from './i18n.js';

    let { orders } = $props();
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
</script>

{#snippet children()}
<div>
    <h1 class="mb-6 text-2xl font-semibold tracking-tight text-stone-900">{t('orders.title', 'Мои заказы')}</h1>

    {#if orders.data.length === 0}
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mb-4 h-12 w-12 text-stone-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
            </svg>
            <p class="text-sm text-stone-500">{t('orders.empty', 'Заказов пока нет')}</p>
        </div>
    {:else}
        <!-- Mobile: card layout -->
        <div class="space-y-3 sm:hidden">
            {#each orders.data as order}
                <a href="/storefront/orders/{order.id}" class="block rounded-2xl border border-stone-200 bg-white p-4 transition-colors hover:border-stone-300">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-sm font-medium text-stone-900">{t('orders.order_number', 'Заказ #:id', { id: order.id })}</span>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {statusColors[order.status]}">
                            {orderStatusLabel(order.status)}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs text-stone-400">
                        <span>{order.placed_at}</span>
                        <span class="font-medium text-stone-700">{order.total_bonus} {t('common.units.bonus_short', 'бон.')}</span>
                    </div>
                </a>
            {/each}
        </div>

        <!-- Desktop: table layout -->
        <div class="hidden overflow-hidden rounded-2xl border border-stone-200 bg-white sm:block">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-stone-100">
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">{t('orders.date', 'Дата')}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-stone-400">{t('orders.status', 'Статус')}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-stone-400">{t('orders.amount', 'Сумма')}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-stone-400">{t('orders.weight', 'Вес')}</th>
                            </tr>
                        </thead>
                    <tbody>
                        {#each orders.data as order}
                            <tr onclick={() => router.visit(`/storefront/orders/${order.id}`)} style="cursor: pointer;" class="border-t border-stone-50 transition-colors hover:bg-stone-50/50">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-stone-900 hover:underline">{order.id}</span>
                                </td>
                                <td class="px-4 py-3 text-stone-500">{order.placed_at}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {statusColors[order.status]}">
                                        {orderStatusLabel(order.status)}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-stone-900">{order.total_bonus} {t('common.units.bonus_short', 'бон.')}</td>
                                <td class="px-4 py-3 text-right text-stone-400">{order.total_weight_grams} {t('common.units.grams_short', 'г')}</td>
                            </tr>
                        {/each}
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        {#if orders.links?.length > 3}
            <nav class="mt-8 flex justify-center gap-1">
                {#each orders.links as link}
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
