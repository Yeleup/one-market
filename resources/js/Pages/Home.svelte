<svelte:head>
    <title>Home</title>
</svelte:head>

<section class="min-h-screen bg-slate-950 px-6 py-16 text-white">
    <div class="mx-auto flex max-w-5xl flex-col gap-10">
        <div class="inline-flex w-fit rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm text-slate-200">
            Inertia v3 + Svelte
        </div>

        <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr]">
            <div class="space-y-6">
                <h1 class="max-w-3xl text-4xl font-semibold tracking-tight text-white sm:text-6xl">
                    One Market now boots through Inertia.
                </h1>

                <p class="max-w-2xl text-lg leading-8 text-slate-300">
                    The Laravel adapter, Svelte client bootstrap, root template, and first page are wired up.
                </p>
            </div>

            <div class="rounded-3xl border border-cyan-400/20 bg-cyan-400/10 p-6 backdrop-blur">
                <p class="text-sm uppercase tracking-[0.2em] text-cyan-200">Status</p>
                <p class="mt-4 text-3xl font-semibold text-white">Installed</p>
                <p class="mt-3 text-sm leading-6 text-slate-300">
                    Route <code>/</code> now renders an Inertia page component named <code>Home</code>.
                </p>
            </div>
        </div>
    </div>
</section>
