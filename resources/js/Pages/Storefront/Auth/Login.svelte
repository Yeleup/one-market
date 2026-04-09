<script>
    import { useForm } from '@inertiajs/svelte';
    import Layout from '../Layout.svelte';

    const form = useForm({
        bin: '',
        password: '',
    });

    function submit(e) {
        e.preventDefault();
        form.post('/storefront/login');
    }
</script>

{#snippet children()}
<div class="flex min-h-[60vh] items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <!-- Header -->
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-semibold tracking-tight text-stone-900">Вход в аккаунт</h1>
            <p class="mt-2 text-sm text-stone-500">Введите ваши данные для входа</p>
        </div>

        <!-- Form card -->
        <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <form onsubmit={submit} class="space-y-5">
                <div>
                    <label for="login-bin" class="mb-1.5 block text-sm font-medium text-stone-700">БИН</label>
                    <input
                        id="login-bin"
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
                    <label for="login-password" class="mb-1.5 block text-sm font-medium text-stone-700">Пароль</label>
                    <input
                        id="login-password"
                        type="password"
                        bind:value={form.password}
                        placeholder="Введите пароль"
                        class="block w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 placeholder-stone-400 transition-colors focus:border-stone-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-stone-900/5"
                    />
                    {#if form.errors.password}
                        <p class="mt-1.5 text-xs text-red-600">{form.errors.password}</p>
                    {/if}
                </div>

                <button
                    type="submit"
                    disabled={form.processing}
                    class="w-full rounded-xl bg-stone-900 px-4 py-3 text-sm font-medium text-white transition-all hover:bg-stone-800 active:scale-[0.98] disabled:opacity-50"
                >
                    {form.processing ? 'Вход...' : 'Войти'}
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-stone-500">
            Нет аккаунта?
            <a href="/storefront/register" class="font-medium text-stone-900 underline decoration-stone-300 underline-offset-4 transition-colors hover:decoration-stone-900">
                Зарегистрироваться
            </a>
        </p>
    </div>
</div>
{/snippet}

<Layout {children} />
