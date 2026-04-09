<script>
    import { useForm } from '@inertiajs/svelte';
    import Layout from './Layout.svelte';
    import { useStorefrontTranslations } from './i18n.js';

    let { client } = $props();
    const { t } = useStorefrontTranslations();

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    function changePassword(e) {
        e.preventDefault();
        passwordForm.put('/storefront/profile/password', {
            onSuccess: () => passwordForm.reset(),
        });
    }
</script>

{#snippet children()}
<div class="mx-auto max-w-lg">
    <h1 class="mb-6 text-2xl font-semibold tracking-tight text-stone-900">{t('profile.title', 'Профиль')}</h1>

    <!-- Client info -->
    <div class="mb-6 rounded-2xl border border-stone-200 bg-white p-5">
        <h2 class="mb-4 text-sm font-semibold text-stone-900">{t('profile.data', 'Данные')}</h2>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-stone-500">{t('profile.first_name', 'Имя')}</span>
                <span class="font-medium text-stone-700">{client.first_name}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-stone-500">{t('profile.last_name', 'Фамилия')}</span>
                <span class="font-medium text-stone-700">{client.last_name}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-stone-500">{t('profile.bin', 'БИН')}</span>
                <span class="font-mono text-xs font-medium text-stone-700">{client.bin}</span>
            </div>
            {#if client.institution}
                <div class="flex justify-between">
                    <span class="text-stone-500">{t('profile.institution', 'Учреждение')}</span>
                    <span class="font-medium text-stone-700">{client.institution.name}</span>
                </div>
            {/if}
            <div class="flex justify-between border-t border-stone-100 pt-3">
                <span class="text-stone-500">{t('profile.balance', 'Баланс')}</span>
                <span class="font-bold text-emerald-600">{client.available_bonuses} {t('common.units.bonuses', 'бонусов')}</span>
            </div>
        </div>
    </div>

    <!-- Change password -->
    <div class="rounded-2xl border border-stone-200 bg-white p-5">
        <h2 class="mb-4 text-sm font-semibold text-stone-900">{t('profile.change_password', 'Сменить пароль')}</h2>
        <form onsubmit={changePassword} class="space-y-4">
            <div>
                <label for="profile-current-pw" class="mb-1.5 block text-sm font-medium text-stone-700">{t('profile.current_password', 'Текущий пароль')}</label>
                <input
                    id="profile-current-pw"
                    type="password"
                    bind:value={passwordForm.current_password}
                    class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 placeholder-stone-400 transition-colors focus:border-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-stone-900/5"
                />
                {#if passwordForm.errors.current_password}
                    <p class="mt-1.5 text-xs text-red-600">{passwordForm.errors.current_password}</p>
                {/if}
            </div>

            <div>
                <label for="profile-new-pw" class="mb-1.5 block text-sm font-medium text-stone-700">{t('profile.new_password', 'Новый пароль')}</label>
                <input
                    id="profile-new-pw"
                    type="password"
                    bind:value={passwordForm.password}
                    class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 placeholder-stone-400 transition-colors focus:border-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-stone-900/5"
                />
                {#if passwordForm.errors.password}
                    <p class="mt-1.5 text-xs text-red-600">{passwordForm.errors.password}</p>
                {/if}
            </div>

            <div>
                <label for="profile-confirm-pw" class="mb-1.5 block text-sm font-medium text-stone-700">{t('profile.password_confirmation', 'Подтвердите пароль')}</label>
                <input
                    id="profile-confirm-pw"
                    type="password"
                    bind:value={passwordForm.password_confirmation}
                    class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 placeholder-stone-400 transition-colors focus:border-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-stone-900/5"
                />
            </div>

            <button
                type="submit"
                disabled={passwordForm.processing}
                class="rounded-xl bg-stone-900 px-5 py-3 text-sm font-medium text-white transition-all hover:bg-stone-800 active:scale-[0.98] disabled:opacity-50"
            >
                {passwordForm.processing ? t('profile.saving', 'Сохранение...') : t('profile.submit', 'Сменить пароль')}
            </button>
        </form>
    </div>
</div>
{/snippet}

<Layout {children} />
