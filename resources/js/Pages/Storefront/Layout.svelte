<script>
    import { router, usePage } from '@inertiajs/svelte';
    import { useStorefrontTranslations } from './i18n.js';

    let { children } = $props();

    const page = usePage();
    const { t } = useStorefrontTranslations();

    let showFlash = $state(true);
    let mobileMenuOpen = $state(false);
    let scrolled = $state(false);

    $effect(() => {
        if (page.props.flash?.success || page.props.flash?.error) {
            showFlash = true;
            const timer = setTimeout(() => showFlash = false, 4000);
            return () => clearTimeout(timer);
        }
    });

    $effect(() => {
        function onScroll() {
            scrolled = window.scrollY > 10;
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    });

    function logout() {
        router.post('/storefront/logout');
    }

    function closeMobileMenu() {
        mobileMenuOpen = false;
    }

    function switchLanguage(languageCode) {
        if (languageCode === page.props.locale?.current) {
            closeMobileMenu();
            return;
        }

        router.post('/storefront/language', {
            language: languageCode,
        }, {
            preserveScroll: true,
            onFinish: closeMobileMenu,
        });
    }

    function languageButtonClass(languageCode, compact = false) {
        return `${compact ? 'px-2.5 py-1 text-[11px]' : 'px-3 py-2 text-xs'} rounded-full font-semibold transition-colors ${
            page.props.locale?.current === languageCode
                ? 'bg-stone-900 text-white'
                : 'bg-white text-stone-500 hover:bg-stone-100 hover:text-stone-900'
        }`;
    }
</script>

<svelte:head>
    <title>One Market</title>
</svelte:head>

<div class="min-h-screen bg-stone-50 font-sans antialiased">
    <!-- Header -->
    <header
        class="sticky top-0 z-50 transition-all duration-300 {scrolled
            ? 'bg-white/80 shadow-sm backdrop-blur-xl'
            : 'bg-white'}"
    >
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="flex h-16 items-center justify-between">
                <!-- Logo -->
                <a href="/storefront" class="text-lg font-semibold tracking-tight text-stone-900">
                    One Market
                </a>

                <!-- Desktop nav -->
                <nav class="hidden items-center gap-1 md:flex">
                    {#if page.props.auth?.client}
                        <a
                            href="/storefront/dashboard"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-stone-600 transition-colors hover:bg-stone-100 hover:text-stone-900"
                        >
                            {t('layout.nav.dashboard', 'Кабинет')}
                        </a>
                        <a
                            href="/storefront/orders"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-stone-600 transition-colors hover:bg-stone-100 hover:text-stone-900"
                        >
                            {t('layout.nav.orders', 'Заказы')}
                        </a>
                        <a
                            href="/storefront/bonuses"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-stone-600 transition-colors hover:bg-stone-100 hover:text-stone-900"
                        >
                            {t('layout.nav.bonuses', 'Бонусы')}
                        </a>
                    {/if}
                </nav>

                <!-- Desktop actions -->
                <div class="hidden items-center gap-3 md:flex">
                    {#if page.props.locale?.available?.length > 1}
                        <div class="flex items-center gap-1 rounded-full border border-stone-200 bg-stone-50 p-1">
                            <span class="pl-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-400">
                                {t('layout.language', 'Язык')}
                            </span>
                            {#each page.props.locale.available as language}
                                <button
                                    type="button"
                                    title={language.name}
                                    onclick={() => switchLanguage(language.code)}
                                    class={languageButtonClass(language.code, true)}
                                >
                                    {language.code.toUpperCase()}
                                </button>
                            {/each}
                        </div>
                    {/if}

                    <a
                        href="/storefront/cart"
                        class="relative rounded-lg p-2 text-stone-600 transition-colors hover:bg-stone-100 hover:text-stone-900"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                        {#if page.props.cart?.count > 0}
                            <span class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-600 text-[10px] font-semibold text-white">
                                {page.props.cart.count}
                            </span>
                        {/if}
                    </a>

                    {#if page.props.auth?.client}
                        <div class="flex items-center gap-3 border-l border-stone-200 pl-3">
                            <span class="text-xs font-medium text-emerald-600">
                                {t('layout.available_bonuses', ':count бонусов', { count: page.props.auth.client.available_bonuses })}
                            </span>
                            <a
                                href="/storefront/profile"
                                class="text-sm font-medium text-stone-700 transition-colors hover:text-stone-900"
                            >
                                {page.props.auth.client.full_name}
                            </a>
                            <button
                                onclick={logout}
                                class="rounded-lg p-2 text-stone-400 transition-colors hover:bg-stone-100 hover:text-stone-600"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                                </svg>
                            </button>
                        </div>
                    {:else}
                        <a
                            href="/storefront/login"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-stone-600 transition-colors hover:bg-stone-100 hover:text-stone-900"
                        >
                            {t('layout.auth.login', 'Войти')}
                        </a>
                        <a
                            href="/storefront/register"
                            class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-stone-800"
                        >
                            {t('layout.auth.register', 'Регистрация')}
                        </a>
                    {/if}
                </div>

                <!-- Mobile: cart + hamburger -->
                <div class="flex items-center gap-2 md:hidden">
                    <a
                        href="/storefront/cart"
                        class="relative rounded-lg p-2 text-stone-600 transition-colors hover:bg-stone-100"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                        {#if page.props.cart?.count > 0}
                            <span class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-600 text-[10px] font-semibold text-white">
                                {page.props.cart.count}
                            </span>
                        {/if}
                    </a>
                    <button
                        onclick={() => mobileMenuOpen = !mobileMenuOpen}
                        class="rounded-lg p-2 text-stone-600 transition-colors hover:bg-stone-100"
                    >
                        {#if mobileMenuOpen}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        {:else}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                            </svg>
                        {/if}
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile dropdown menu -->
        {#if mobileMenuOpen}
            <div class="border-t border-stone-100 bg-white md:hidden">
                <div class="space-y-1 px-4 pb-4 pt-3">
                    <a onclick={closeMobileMenu} href="/storefront" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-stone-700 transition-colors hover:bg-stone-50">{t('layout.nav.catalog', 'Каталог')}</a>
                    {#if page.props.auth?.client}
                        <a onclick={closeMobileMenu} href="/storefront/dashboard" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-stone-700 transition-colors hover:bg-stone-50">{t('layout.nav.dashboard', 'Кабинет')}</a>
                        <a onclick={closeMobileMenu} href="/storefront/orders" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-stone-700 transition-colors hover:bg-stone-50">{t('layout.nav.orders', 'Заказы')}</a>
                        <a onclick={closeMobileMenu} href="/storefront/bonuses" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-stone-700 transition-colors hover:bg-stone-50">{t('layout.nav.bonuses', 'Бонусы')}</a>
                        <a onclick={closeMobileMenu} href="/storefront/profile" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-stone-700 transition-colors hover:bg-stone-50">{t('layout.nav.profile', 'Профиль')}</a>
                        <div class="border-t border-stone-100 pt-2">
                            <div class="mb-2 px-3 text-xs font-medium text-emerald-600">
                                {t('layout.available_bonuses', ':count бонусов', { count: page.props.auth.client.available_bonuses })}
                            </div>
                            <button onclick={() => { closeMobileMenu(); logout(); }} class="w-full rounded-lg px-3 py-2.5 text-left text-sm font-medium text-red-600 transition-colors hover:bg-red-50">
                                {t('layout.auth.logout', 'Выйти')}
                            </button>
                        </div>
                    {:else}
                        <div class="flex gap-2 border-t border-stone-100 pt-3">
                            <a onclick={closeMobileMenu} href="/storefront/login" class="flex-1 rounded-lg border border-stone-200 px-3 py-2.5 text-center text-sm font-medium text-stone-700 transition-colors hover:bg-stone-50">
                                {t('layout.auth.login', 'Войти')}
                            </a>
                            <a onclick={closeMobileMenu} href="/storefront/register" class="flex-1 rounded-lg bg-stone-900 px-3 py-2.5 text-center text-sm font-medium text-white transition-colors hover:bg-stone-800">
                                {t('layout.auth.register', 'Регистрация')}
                            </a>
                        </div>
                    {/if}

                    {#if page.props.locale?.available?.length > 1}
                        <div class="border-t border-stone-100 pt-3">
                            <div class="mb-2 px-3 text-xs font-semibold uppercase tracking-[0.18em] text-stone-400">
                                {t('layout.language', 'Язык')}
                            </div>
                            <div class="grid grid-cols-1 gap-2 px-3">
                                {#each page.props.locale.available as language}
                                    <button
                                        type="button"
                                        onclick={() => switchLanguage(language.code)}
                                        class={languageButtonClass(language.code)}
                                    >
                                        {language.name}
                                    </button>
                                {/each}
                            </div>
                        </div>
                    {/if}
                </div>
            </div>
        {/if}
    </header>

    <!-- Flash messages -->
    {#if showFlash && page.props.flash?.success}
        <div class="mx-auto max-w-6xl px-4 pt-4 sm:px-6">
            <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {page.props.flash.success}
            </div>
        </div>
    {/if}
    {#if showFlash && page.props.flash?.error}
        <div class="mx-auto max-w-6xl px-4 pt-4 sm:px-6">
            <div class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                {page.props.flash.error}
            </div>
        </div>
    {/if}

    <!-- Main content -->
    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        {@render children()}
    </main>

    <!-- Footer -->
    <footer class="border-t border-stone-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <span class="text-sm font-medium text-stone-900">One Market</span>
                <span class="text-xs text-stone-400">&copy; {new Date().getFullYear()} {t('layout.footer_copyright', 'Все права защищены')}</span>
            </div>
        </div>
    </footer>
</div>
