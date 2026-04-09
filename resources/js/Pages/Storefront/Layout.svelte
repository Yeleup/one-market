<script>
    import { router, usePage } from '@inertiajs/svelte';

    let { children } = $props();

    const page = usePage();

    let showFlash = $state(true);

    $effect(() => {
        if (page.props.flash?.success || page.props.flash?.error) {
            showFlash = true;
            const timer = setTimeout(() => showFlash = false, 4000);
            return () => clearTimeout(timer);
        }
    });

    function logout() {
        router.post('/storefront/logout');
    }
</script>

<svelte:head>
    <title>One Market</title>
</svelte:head>

<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center gap-8">
                    <a href="/storefront" class="text-xl font-bold text-gray-900">One Market</a>
                    <nav class="hidden items-center gap-4 md:flex">
                        <a href="/storefront" class="text-sm text-gray-600 hover:text-gray-900">Каталог</a>
                        {#if page.props.auth?.client}
                            <a href="/storefront/dashboard" class="text-sm text-gray-600 hover:text-gray-900">Кабинет</a>
                            <a href="/storefront/orders" class="text-sm text-gray-600 hover:text-gray-900">Заказы</a>
                            <a href="/storefront/bonuses" class="text-sm text-gray-600 hover:text-gray-900">Бонусы</a>
                        {/if}
                    </nav>
                </div>

                <div class="flex items-center gap-4">
                    <a href="/storefront/cart" class="relative text-sm text-gray-600 hover:text-gray-900">
                        Корзина
                        {#if page.props.cart?.count > 0}
                            <span class="absolute -right-3 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-xs text-white">
                                {page.props.cart.count}
                            </span>
                        {/if}
                    </a>

                    {#if page.props.auth?.client}
                        <span class="text-sm text-gray-500">
                            {page.props.auth.client.available_bonuses} бонусов
                        </span>
                        <a href="/storefront/profile" class="text-sm text-gray-600 hover:text-gray-900">
                            {page.props.auth.client.full_name}
                        </a>
                        <button onclick={logout} class="text-sm text-red-600 hover:text-red-800">
                            Выйти
                        </button>
                    {:else}
                        <a href="/storefront/login" class="text-sm text-gray-600 hover:text-gray-900">Войти</a>
                        <a href="/storefront/register" class="rounded-md bg-blue-600 px-3 py-2 text-sm text-white hover:bg-blue-700">
                            Регистрация
                        </a>
                    {/if}
                </div>
            </div>
        </div>
    </header>

    <!-- Flash messages -->
    {#if showFlash && page.props.flash?.success}
        <div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
            <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">
                {page.props.flash.success}
            </div>
        </div>
    {/if}
    {#if showFlash && page.props.flash?.error}
        <div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
            <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">
                {page.props.flash.error}
            </div>
        </div>
    {/if}

    <!-- Main content -->
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {@render children()}
    </main>

    <!-- Footer -->
    <footer class="border-t bg-white py-8">
        <div class="mx-auto max-w-7xl px-4 text-center text-sm text-gray-500 sm:px-6 lg:px-8">
            &copy; {new Date().getFullYear()} One Market
        </div>
    </footer>
</div>
