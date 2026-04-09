<script>
    import { useForm } from '@inertiajs/svelte';
    import Layout from '../Layout.svelte';

    const form = useForm({
        bin: '',
        password: '',
    });

    function submit(e) {
        e.preventDefault();
        $form.post('/storefront/login');
    }
</script>

{#snippet children()}
<div class="mx-auto max-w-md">
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Вход</h1>

    <form onsubmit={submit} class="space-y-4">
        <div>
            <label for="bin" class="block text-sm font-medium text-gray-700">БИН</label>
            <input
                id="bin"
                type="text"
                bind:value={$form.bin}
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                maxlength="12"
            />
            {#if $form.errors.bin}
                <p class="mt-1 text-sm text-red-600">{$form.errors.bin}</p>
            {/if}
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Пароль</label>
            <input
                id="password"
                type="password"
                bind:value={$form.password}
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
            {#if $form.errors.password}
                <p class="mt-1 text-sm text-red-600">{$form.errors.password}</p>
            {/if}
        </div>

        <button
            type="submit"
            disabled={$form.processing}
            class="w-full rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
        >
            {$form.processing ? 'Вход...' : 'Войти'}
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-gray-600">
        Нет аккаунта? <a href="/storefront/register" class="text-blue-600 hover:underline">Зарегистрироваться</a>
    </p>
</div>
{/snippet}

<Layout {children} />
