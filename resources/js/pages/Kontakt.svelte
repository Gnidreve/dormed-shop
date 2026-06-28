<script lang="ts">
    import Clock from 'lucide-svelte/icons/clock';
    import Mail from 'lucide-svelte/icons/mail';
    import MapPin from 'lucide-svelte/icons/map-pin';
    import Phone from 'lucide-svelte/icons/phone';
    import AppFooter from '@/components/AppFooter.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ShopHeader from '@/components/ShopHeader.svelte';

    const locations = [
        {
            name: 'Holzwickede',
            isZentrale: true,
            street: 'Wilhelm-Röntgen-Straße 4',
            city: '59439 Holzwickede',
            phone: '+49 (0) 2301 / 188-600',
            fax: '+49 (0) 2301 / 188-620',
            email: 'mail@dormed.de',
        },
        {
            name: 'Dortmund',
            isZentrale: false,
            street: 'Musterstraße 12',
            city: '44135 Dortmund',
            phone: '+49 (0) 231 / 123-456',
            fax: '+49 (0) 231 / 123-457',
            email: 'dortmund@dormed.de',
        },
        {
            name: 'Essen',
            isZentrale: false,
            street: 'Beispielweg 8',
            city: '45127 Essen',
            phone: '+49 (0) 201 / 234-567',
            fax: '+49 (0) 201 / 234-568',
            email: 'essen@dormed.de',
        },
        {
            name: 'Münster',
            isZentrale: false,
            street: 'Röntgenallee 3',
            city: '48143 Münster',
            phone: '+49 (0) 251 / 345-678',
            fax: '+49 (0) 251 / 345-679',
            email: 'muenster@dormed.de',
        },
    ];
</script>

<AppHead title="Kontakt" />

<div class="flex min-h-screen flex-col bg-white">
    <ShopHeader />

    <main class="flex-1 mx-auto max-w-5xl px-4 py-12 lg:px-8">
        <h1 class="mb-2 text-2xl font-bold text-[#1a3a5c]">Kontakt & Standorte</h1>
        <p class="mb-10 text-sm text-gray-500">
            Wir empfangen Sie ausschließlich nach vorheriger Terminvereinbarung.
        </p>

        <!-- Location cards -->
        <div class="grid gap-5 sm:grid-cols-2">
            {#each locations as loc (loc.name)}
                <div class="rounded-lg border bg-white p-6 {loc.isZentrale ? 'border-[#1a6bbf]/40 shadow-sm' : ''}">
                    <div class="mb-4 flex items-center gap-2">
                        <h2 class="text-base font-bold text-[#1a3a5c]">{loc.name}</h2>
                        {#if loc.isZentrale}
                            <span class="rounded-full bg-[#1a6bbf]/10 px-2 py-0.5 text-xs font-medium text-[#1a6bbf]">
                                Zentrale
                            </span>
                        {/if}
                    </div>

                    <div class="space-y-2.5 text-sm text-gray-600">
                        <div class="flex items-start gap-2.5">
                            <MapPin class="mt-0.5 size-4 shrink-0 text-gray-400" />
                            <span>
                                {loc.street}<br />
                                {loc.city}
                            </span>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <Phone class="size-4 shrink-0 text-gray-400" />
                            <a href="tel:{loc.phone.replace(/\s|\//g, '')}" class="hover:text-[#1a6bbf]">
                                {loc.phone}
                            </a>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <Mail class="size-4 shrink-0 text-gray-400" />
                            <a href="mailto:{loc.email}" class="hover:text-[#1a6bbf]">
                                {loc.email}
                            </a>
                        </div>
                    </div>
                </div>
            {/each}
        </div>

        <!-- Opening hours banner -->
        <div class="mt-10 flex items-start gap-3 rounded-lg border border-[#1a6bbf]/20 bg-[#1a6bbf]/5 px-5 py-4">
            <Clock class="mt-0.5 size-4 shrink-0 text-[#1a6bbf]" />
            <div class="text-sm text-gray-700">
                <p class="font-semibold text-[#1a3a5c]">Öffnungszeiten (alle Standorte)</p>
                <p class="mt-1">Mo – Do: 08:00 – 17:00 Uhr &nbsp;·&nbsp; Fr: 08:00 – 14:00 Uhr</p>
                <p class="mt-0.5 text-gray-500">Besuche nur nach Terminvereinbarung</p>
            </div>
        </div>
    </main>

    <AppFooter />
</div>
