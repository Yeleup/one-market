<script>
    import { useForm } from '@inertiajs/svelte';
    import Layout from '../Layout.svelte';

    let { institutions } = $props();

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
        form.post('/storefront/register');
    }
</script>

{#snippet children()}
<div class="flex min-h-[60vh] items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <!-- Header -->
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-semibold tracking-tight text-stone-900">Создать аккаунт</h1>
            <p class="mt-2 text-sm text-stone-500">Заполните данные для регистрации</p>
        </div>

        <!-- Form card -->
        <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <form onsubmit={submit} class="space-y-5">
                <!-- Name fields in a row on larger screens -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="reg-first-name" class="mb-1.5 block text-sm font-medium text-stone-700">Имя</label>
                        <input
                            id="reg-first-name"
                            type="text"
                            bind:value={form.first_name}
                            placeholder="Имя"
                            class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 placeholder-stone-400 transition-colors focus:border-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-stone-900/5"
                        />
                        {#if form.errors.first_name}
                            <p class="mt-1.5 text-xs text-red-600">{form.errors.first_name}</p>
                        {/if}
                    </div>
                    <div>
                        <label for="reg-last-name" class="mb-1.5 block text-sm font-medium text-stone-700">Фамилия</label>
                        <input
                            id="reg-last-name"
                            type="text"
                            bind:value={form.last_name}
                            placeholder="Фамилия"
                            class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 placeholder-stone-400 transition-colors focus:border-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-stone-900/5"
                        />
                        {#if form.errors.last_name}
                            <p class="mt-1.5 text-xs text-red-600">{form.errors.last_name}</p>
                        {/if}
                    </div>
                </div>

                <div>
                    <label for="reg-bin" class="mb-1.5 block text-sm font-medium text-stone-700">БИН</label>
                    <input
                        id="reg-bin"
                        type="text"
                        bind:value={form.bin}
                        placeholder="Введите БИН"
                        class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 placeholder-stone-400 transition-colors focus:border-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-stone-900/5"
                        maxlength="12"
                    />
                    {#if form.errors.bin}
                        <p class="mt-1.5 text-xs text-red-600">{form.errors.bin}</p>
                    {/if}
                </div>

                <div>
                    <label for="reg-institution" class="mb-1.5 block text-sm font-medium text-stone-700">Учреждение</label>
                    <select
                        id="reg-institution"
                        bind:value={form.institution_id}
                        class="block w-full appearance-none rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 transition-colors focus:border-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-stone-900/5"
                    >
                        <option value="">— Не выбрано —</option>
                        {#each institutions as inst}
                            <option value={inst.id}>{inst.name}</option>
                        {/each}
                    </select>
                    {#if form.errors.institution_id}
                        <p class="mt-1.5 text-xs text-red-600">{form.errors.institution_id}</p>
                    {/if}
                </div>

                <div>
                    <label for="reg-password" class="mb-1.5 block text-sm font-medium text-stone-700">Пароль</label>
                    <input
                        id="reg-password"
                        type="password"
                        bind:value={form.password}
                        placeholder="Минимум 8 символов"
                        class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 placeholder-stone-400 transition-colors focus:border-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-stone-900/5"
                    />
                    {#if form.errors.password}
                        <p class="mt-1.5 text-xs text-red-600">{form.errors.password}</p>
                    {/if}
                </div>

                <div>
                    <label for="reg-password-confirm" class="mb-1.5 block text-sm font-medium text-stone-700">Подтвердите пароль</label>
                    <input
                        id="reg-password-confirm"
                        type="password"
                        bind:value={form.password_confirmation}
                        placeholder="Повторите пароль"
                        class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 placeholder-stone-400 transition-colors focus:border-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-stone-900/5"
                    />
                </div>

                <button
                    type="submit"
                    disabled={form.processing}
                    class="w-full rounded-xl bg-stone-900 px-4 py-3 text-sm font-medium text-white transition-all hover:bg-stone-800 active:scale-[0.98] disabled:opacity-50"
                >
                    {form.processing ? 'Регистрация...' : 'Зарегистрироваться'}
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-stone-500">
            Уже есть аккаунт?
            <a href="/storefront/login" class="font-medium text-stone-900 underline decoration-stone-300 underline-offset-4 transition-colors hover:decoration-stone-900">
                Войти
            </a>
        </p>
    </div>
</div>
{/snippet}

<Layout {children} />
