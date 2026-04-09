<script>
    import { useForm } from '@inertiajs/svelte';
    import Layout from './Layout.svelte';

    let { client } = $props();

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    function changePassword(e) {
        e.preventDefault();
        $passwordForm.put('/storefront/profile/password', {
            onSuccess: () => $passwordForm.reset(),
        });
    }
</script>

{#snippet children()}
<div class="mx-auto max-w-2xl">
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Профиль</h1>

    <!-- Client info -->
    <div class="mb-8 rounded-lg border bg-white p-6">
        <h2 class="mb-4 text-lg font-semibold text-gray-900">Данные</h2>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600">Имя:</span>
                <span class="font-medium">{client.first_name}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Фамилия:</span>
                <span class="font-medium">{client.last_name}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">БИН:</span>
                <span class="font-medium">{client.bin}</span>
            </div>
            {#if client.institution}
                <div class="flex justify-between">
                    <span class="text-gray-600">Учреждение:</span>
                    <span class="font-medium">{client.institution.name}</span>
                </div>
            {/if}
            <div class="flex justify-between border-t pt-3">
                <span class="text-gray-600">Баланс:</span>
                <span class="font-bold text-green-600">{client.available_bonuses} бонусов</span>
            </div>
        </div>
    </div>

    <!-- Change password -->
    <div class="rounded-lg border bg-white p-6">
        <h2 class="mb-4 text-lg font-semibold text-gray-900">Сменить пароль</h2>
        <form onsubmit={changePassword} class="space-y-4">
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700">Текущий пароль</label>
                <input
                    id="current_password"
                    type="password"
                    bind:value={$passwordForm.current_password}
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
                {#if $passwordForm.errors.current_password}
                    <p class="mt-1 text-sm text-red-600">{$passwordForm.errors.current_password}</p>
                {/if}
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Новый пароль</label>
                <input
                    id="password"
                    type="password"
                    bind:value={$passwordForm.password}
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
                {#if $passwordForm.errors.password}
                    <p class="mt-1 text-sm text-red-600">{$passwordForm.errors.password}</p>
                {/if}
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Подтвердите пароль</label>
                <input
                    id="password_confirmation"
                    type="password"
                    bind:value={$passwordForm.password_confirmation}
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                />
            </div>

            <button
                type="submit"
                disabled={$passwordForm.processing}
                class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
            >
                {$passwordForm.processing ? 'Сохранение...' : 'Сменить пароль'}
            </button>
        </form>
    </div>
</div>
{/snippet}

<Layout {children} />
