<script>
    import { useForm } from '@inertiajs/svelte';
    import Layout from '../Layout.svelte';
    import { useStorefrontTranslations } from '../i18n.js';

    let { institutions } = $props();
    const { t } = useStorefrontTranslations();

    const form = useForm({
        first_name: '',
        last_name: '',
        bin: '',
        password: '',
        password_confirmation: '',
        institution_id: '',
    });

    function submit(e) {
        e.preventDefault();
        form.post('/register');
    }
</script>

{#snippet children()}
<div class="flex min-h-[60vh] items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <!-- Header -->
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-semibold tracking-tight text-stone-900">{t('auth.register.title', 'Создать аккаунт')}</h1>
            <p class="mt-2 text-sm text-stone-500">{t('auth.register.subtitle', 'Заполните данные для регистрации')}</p>
        </div>

        <!-- Form card -->
        <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <form onsubmit={submit} class="space-y-5">
                <!-- Name fields in a row on larger screens -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="reg-first-name" class="mb-1.5 block text-sm font-medium text-stone-700">{t('auth.register.first_name', 'Имя')}</label>
                        <input
                            id="reg-first-name"
                            type="text"
                            bind:value={form.first_name}
                            placeholder={t('auth.register.first_name', 'Имя')}
                            class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 placeholder-stone-400 transition-colors focus:border-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-stone-900/5"
                        />
                        {#if form.errors.first_name}
                            <p class="mt-1.5 text-xs text-red-600">{form.errors.first_name}</p>
                        {/if}
                    </div>
                    <div>
                        <label for="reg-last-name" class="mb-1.5 block text-sm font-medium text-stone-700">{t('auth.register.last_name', 'Фамилия')}</label>
                        <input
                            id="reg-last-name"
                            type="text"
                            bind:value={form.last_name}
                            placeholder={t('auth.register.last_name', 'Фамилия')}
                            class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 placeholder-stone-400 transition-colors focus:border-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-stone-900/5"
                        />
                        {#if form.errors.last_name}
                            <p class="mt-1.5 text-xs text-red-600">{form.errors.last_name}</p>
                        {/if}
                    </div>
                </div>

                <div>
                    <label for="reg-bin" class="mb-1.5 block text-sm font-medium text-stone-700">{t('auth.register.bin', 'БИН')}</label>
                    <input
                        id="reg-bin"
                        type="text"
                        bind:value={form.bin}
                        placeholder={t('auth.register.bin_placeholder', 'Введите БИН')}
                        class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 placeholder-stone-400 transition-colors focus:border-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-stone-900/5"
                        maxlength="12"
                    />
                    {#if form.errors.bin}
                        <p class="mt-1.5 text-xs text-red-600">{form.errors.bin}</p>
                    {/if}
                </div>

                <div>
                    <label for="reg-institution" class="mb-1.5 block text-sm font-medium text-stone-700">{t('auth.register.institution', 'Учреждение')}</label>
                    <select
                        id="reg-institution"
                        bind:value={form.institution_id}
                        class="block w-full appearance-none rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 transition-colors focus:border-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-stone-900/5"
                    >
                        <option value="">{t('auth.register.institution_placeholder', '— Не выбрано —')}</option>
                        {#each institutions as inst}
                            <option value={inst.id}>{inst.name}</option>
                        {/each}
                    </select>
                    {#if form.errors.institution_id}
                        <p class="mt-1.5 text-xs text-red-600">{form.errors.institution_id}</p>
                    {/if}
                </div>

                <div>
                    <label for="reg-password" class="mb-1.5 block text-sm font-medium text-stone-700">{t('auth.register.password', 'Пароль')}</label>
                    <input
                        id="reg-password"
                        type="password"
                        bind:value={form.password}
                        placeholder={t('auth.register.password_placeholder', 'Минимум 8 символов')}
                        class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 placeholder-stone-400 transition-colors focus:border-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-stone-900/5"
                    />
                    {#if form.errors.password}
                        <p class="mt-1.5 text-xs text-red-600">{form.errors.password}</p>
                    {/if}
                </div>

                <div>
                    <label for="reg-password-confirm" class="mb-1.5 block text-sm font-medium text-stone-700">{t('auth.register.password_confirmation', 'Подтвердите пароль')}</label>
                    <input
                        id="reg-password-confirm"
                        type="password"
                        bind:value={form.password_confirmation}
                        placeholder={t('auth.register.password_confirmation_placeholder', 'Повторите пароль')}
                        class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 placeholder-stone-400 transition-colors focus:border-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-stone-900/5"
                    />
                </div>

                <button
                    type="submit"
                    disabled={form.processing}
                    class="w-full rounded-xl bg-stone-900 px-4 py-3 text-sm font-medium text-white transition-all hover:bg-stone-800 active:scale-[0.98] disabled:opacity-50"
                >
                    {form.processing ? t('auth.register.submitting', 'Регистрация...') : t('auth.register.submit', 'Зарегистрироваться')}
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-stone-500">
            {t('auth.register.have_account', 'Уже есть аккаунт?')}
            <a href="/login" class="font-medium text-stone-900 underline decoration-stone-300 underline-offset-4 transition-colors hover:decoration-stone-900">
                {t('auth.register.login', 'Войти')}
            </a>
        </p>
    </div>
</div>
{/snippet}

<Layout {children} />
