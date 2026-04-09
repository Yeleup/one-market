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
        $form.post('/storefront/register');
    }
</script>

{#snippet children()}
<div class="mx-auto max-w-md">
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Регистрация</h1>

    <form onsubmit={submit} class="space-y-4">
        <div>
            <label for="first_name" class="block text-sm font-medium text-gray-700">Имя</label>
            <input
                id="first_name"
                type="text"
                bind:value={$form.first_name}
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
            {#if $form.errors.first_name}
                <p class="mt-1 text-sm text-red-600">{$form.errors.first_name}</p>
            {/if}
        </div>

        <div>
            <label for="last_name" class="block text-sm font-medium text-gray-700">Фамилия</label>
            <input
                id="last_name"
                type="text"
                bind:value={$form.last_name}
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
            {#if $form.errors.last_name}
                <p class="mt-1 text-sm text-red-600">{$form.errors.last_name}</p>
            {/if}
        </div>

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
            <label for="institution_id" class="block text-sm font-medium text-gray-700">Учреждение</label>
            <select
                id="institution_id"
                bind:value={$form.institution_id}
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            >
                <option value="">— Не выбрано —</option>
                {#each institutions as inst}
                    <option value={inst.id}>{inst.name}</option>
                {/each}
            </select>
            {#if $form.errors.institution_id}
                <p class="mt-1 text-sm text-red-600">{$form.errors.institution_id}</p>
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

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Подтвердите пароль</label>
            <input
                id="password_confirmation"
                type="password"
                bind:value={$form.password_confirmation}
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
        </div>

        <button
            type="submit"
            disabled={$form.processing}
            class="w-full rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
        >
            {$form.processing ? 'Регистрация...' : 'Зарегистрироваться'}
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-gray-600">
        Уже есть аккаунт? <a href="/storefront/login" class="text-blue-600 hover:underline">Войти</a>
    </p>
</div>
{/snippet}

<Layout {children} />
