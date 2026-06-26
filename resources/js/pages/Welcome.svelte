<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ArrowRight from 'lucide-svelte/icons/arrow-right';
    import Check from 'lucide-svelte/icons/check';
    import FileCheck from 'lucide-svelte/icons/file-check';
    import PackageCheck from 'lucide-svelte/icons/package-check';
    import Phone from 'lucide-svelte/icons/phone';
    import ShieldCheck from 'lucide-svelte/icons/shield-check';
    import Truck from 'lucide-svelte/icons/truck';
    import * as ProductController from '@/actions/App/Http/Controllers/ProductController';
    import AppFooter from '@/components/AppFooter.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ShopHeader from '@/components/ShopHeader.svelte';

    const trustItems = [
        {
            icon: PackageCheck,
            label: 'direkt bestellen',
        },
        {
            icon: Truck,
            label: 'Lieferung oder Abholung',
        },
        {
            icon: FileCheck,
            label: 'inkl. GWE und Einweisung',
        },
        {
            icon: ShieldCheck,
            label: 'Gewährleistung und geprüfte Qualität',
        },
    ] as const;

    const features = [
        'Diagnostik',
        'Notfallmedizin',
        'Monitoring',
        'Zubehör & Verbrauchsmaterial',
    ] as const;

    const onboardingSteps = [
        {
            title: 'Online registrieren',
            text: 'Erstellen Sie Ihr persönliches Kundenkonto in wenigen Minuten und starten Sie ohne Umwege in die Beschaffung.',
        },
        {
            title: 'Sortiment freischalten',
            text: 'Nach kurzer Prüfung erhalten Sie Zugang zu Ihrem Sortiment und zu den für Sie passenden Konditionen.',
        },
        {
            title: 'Schnell bestellen',
            text: 'Vom Verbrauchsmaterial bis zur Medizintechnik bestellen Sie mit wenigen Klicks und klarer Betreuung.',
        },
    ] as const;

    const highlightProducts = [
        {
            title: 'Meone Finger-Pulsoximeter',
            price: '46,41 €*',
            summary: 'USB-C aufladbar, OLED-Display, Spot-Check oder kontinuierliche Überwachung.',
        },
        {
            title: 'BP2 Blutdruckmessgerät mit 1-Kanal-EKG',
            price: '117,81 €*',
            summary: 'Kompaktes 2-in-1-Gerät mit EKG-Funktion, App-Anbindung und umfangreichem Zubehör.',
        },
        {
            title: 'Wandkasten Metall mit Alarm',
            price: '189,21 €*',
            summary: 'Robuster Schutz für Defibrillatoren mit akustischem und visuellem Alarmsystem.',
        },
    ] as const;

    const serviceColumns = [
        {
            eyebrow: 'Praxis & Klinik',
            title: 'Vom Erstgerät bis zur Erweiterung',
            text: 'Beschaffen Sie Diagnostik, Monitoring und Zubehör zentral an einem Ort und mit nachvollziehbarer Betreuung.',
        },
        {
            eyebrow: 'Beratung & Einweisung',
            title: 'Nicht nur bestellen, sondern passend auswählen',
            text: 'Für erklärungsbedürftige Technik bleiben Lieferung, Gewährleistung und Einweisung Teil des Gesamtbilds.',
        },
        {
            eyebrow: 'Schnelle Wege',
            title: 'Abholung oder Lieferung, je nach Einsatz',
            text: 'Ob kurzfristiger Bedarf oder geplante Ausstattung: die Shop-Oberfläche soll auf reale Einkaufsabläufe einzahlen.',
        },
    ] as const;

    const assortmentPillars = [
        'Verlässliche Medizintechnik für Praxis, MVZ und Klinik',
        'Kompakte Geräte für Alltag, Hausbesuch und Notfall',
        'Zubehör und Verbrauchsmaterial als wiederkehrender Bedarf',
        'Persönlicher Ansprechpartner statt anonymer Bestellstrecke',
    ] as const;
</script>

<AppHead title="Willkommen" />

<div class="min-h-screen bg-white">
    <ShopHeader />

    <!-- Hero -->
    <section class="relative overflow-hidden">
        <!-- Background image fills the section -->
        <img
            src="/assets/hero.png"
            alt=""
            aria-hidden="true"
            class="absolute inset-0 h-full w-full object-cover object-center"
        />
        <!-- Gradient overlay: left side fully opaque navy, fades to transparent on the right -->
        <div
            class="absolute inset-0"
            style="background: linear-gradient(to right, #0d1f44 38%, #0d1f4499 58%, transparent 72%)"
        ></div>

        <!-- Content -->
        <div class="relative z-10 mx-auto max-w-7xl px-8 py-16 lg:px-8 lg:py-20">
            <div class="max-w-lg">
                <h1 class="mb-3 text-3xl font-bold leading-tight text-white lg:text-4xl">
                    Wir kümmern uns um Ihre Praxis
                </h1>
                <p class="mb-7 text-base text-white/75">
                    Verlässliche Medizintechnik für Ihre Patientenversorgung
                </p>

                <ul class="mb-10 flex flex-col gap-2.5">
                    {#each features as feature}
                        <li class="flex items-center gap-2.5 font-semibold text-white">
                            <Check class="size-4 shrink-0" />
                            {feature}
                        </li>
                    {/each}
                </ul>

                <a
                    href="tel:023011886000"
                    class="group inline-flex items-center gap-4 rounded-xl bg-white/10 px-5 py-3.5 backdrop-blur-sm transition hover:bg-white/20"
                >
                    <div class="rounded-full bg-white/20 p-2.5 transition group-hover:bg-white/30">
                        <Phone class="size-5 text-white" />
                    </div>
                    <span class="text-2xl font-bold tracking-wide text-white lg:text-3xl">
                        02301 – 188/600
                    </span>
                </a>
            </div>
        </div>
    </section>

    <!-- Trust bar -->
    <div class="border-b border-t bg-white">
        <div class="mx-auto max-w-7xl px-4 lg:px-8">
            <div class="grid grid-cols-2 divide-x divide-y lg:grid-cols-4 lg:divide-y-0">
                {#each trustItems as item}
                    <div class="flex items-center gap-3 px-6 py-5">
                        <item.icon class="size-8 shrink-0 text-[#1a6bbf]" strokeWidth={1.5} />
                        <span class="text-sm font-semibold leading-snug text-[#0d1f44]">
                            {item.label}
                        </span>
                    </div>
                {/each}
            </div>
        </div>
    </div>

    <section class="bg-gray-50">
        <div class="mx-auto max-w-7xl px-4 py-12 lg:px-8 lg:py-16">
            <div class="max-w-3xl">
                <h2 class="text-2xl font-bold text-gray-900 lg:text-3xl">
                    Einfach registrieren, freischalten lassen und direkt bestellen
                </h2>
                <p class="mt-3 text-sm leading-6 text-muted-foreground lg:text-base">
                    Der bisherige Auftritt von dormed24 lebt stark davon, dass neue Kunden schnell
                    verstehen, wie sie ins Sortiment kommen. Genau dieses Prinzip soll die
                    Startseite auch unterhalb des Headers sauber fortführen.
                </p>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-3">
                {#each onboardingSteps as step, index}
                    <article class="rounded-xl border bg-white p-6 shadow-sm">
                        <p class="text-sm font-semibold text-[#1a6bbf]">Schritt {index + 1}</p>
                        <h3 class="mt-2 text-lg font-semibold text-gray-900">{step.title}</h3>
                        <p class="mt-3 text-sm leading-6 text-muted-foreground">{step.text}</p>
                    </article>
                {/each}
            </div>
        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-4 py-12 lg:px-8 lg:py-16">
            <div class="mb-8 flex items-end justify-between gap-6">
                <div class="max-w-3xl">
                    <h2 class="text-2xl font-bold text-gray-900 lg:text-3xl">
                        Neu bei dormed24
                    </h2>
                    <p class="mt-3 text-sm leading-6 text-muted-foreground lg:text-base">
                        Im aktuellen Live-Shop stehen kompakte Geräte für Diagnostik und
                        Notfallversorgung sichtbar im Vordergrund. Dieser Bereich greift das auf,
                        ohne vom restlichen Shop-Design wegzulaufen.
                    </p>
                </div>
                <Link
                    href={ProductController.index.url()}
                    class="hidden shrink-0 text-sm font-semibold text-[#1a6bbf] hover:underline lg:inline-flex lg:items-center lg:gap-2"
                >
                    Alle Produkte ansehen
                    <ArrowRight class="size-4" />
                </Link>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                {#each highlightProducts as product}
                    <article class="rounded-xl border bg-white p-6 shadow-sm transition hover:shadow-md">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#1a6bbf]">
                            Produkthighlight
                        </p>
                        <h3 class="mt-3 text-lg font-semibold text-gray-900">{product.title}</h3>
                        <p class="mt-3 text-sm leading-6 text-muted-foreground">
                            {product.summary}
                        </p>
                        <div class="mt-5 flex items-center justify-between gap-4 border-t pt-4">
                            <span class="text-sm font-semibold text-[#1a3a5c]">{product.price}</span>
                            <Link
                                href={ProductController.index.url()}
                                class="text-sm font-semibold text-[#1a6bbf] hover:underline"
                            >
                                Zum Sortiment
                            </Link>
                        </div>
                    </article>
                {/each}
            </div>

            <div class="mt-6 lg:hidden">
                <Link
                    href={ProductController.index.url()}
                    class="inline-flex items-center gap-2 text-sm font-semibold text-[#1a6bbf] hover:underline"
                >
                    Alle Produkte ansehen
                    <ArrowRight class="size-4" />
                </Link>
            </div>
        </div>
    </section>

    <section class="bg-gray-50">
        <div class="mx-auto max-w-7xl px-4 py-12 lg:px-8 lg:py-16">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 lg:text-3xl">
                        Beschaffung für Praxis, MVZ und Klinik
                    </h2>
                    <p class="mt-3 text-sm leading-6 text-muted-foreground lg:text-base">
                        Statt einer komplett eigenen Landingpage-Ästhetik bekommt der Shop hier
                        eher denselben nüchternen Ton wie die anderen Seiten: klare Informationen,
                        erkennbare Vorteile und direkte Wege in Sortiment oder Kontakt.
                    </p>

                    <div class="mt-6 grid gap-3">
                        {#each assortmentPillars as pillar}
                            <div class="rounded-lg border bg-white px-4 py-3 text-sm text-gray-700 shadow-sm">
                                {pillar}
                            </div>
                        {/each}
                    </div>
                </div>

                <div class="rounded-xl border bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Beratung und schnelle Wege</h3>
                    <div class="mt-5 space-y-5">
                        {#each serviceColumns as column}
                            <div class="border-b pb-5 last:border-b-0 last:pb-0">
                                <p class="text-sm font-semibold text-[#1a3a5c]">{column.title}</p>
                                <p class="mt-2 text-sm leading-6 text-muted-foreground">
                                    {column.text}
                                </p>
                            </div>
                        {/each}
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link
                            href={ProductController.index.url()}
                            class="inline-flex items-center gap-2 rounded-lg bg-[#0d1f44] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#0d1f44]/90"
                        >
                            Jetzt shoppen
                            <ArrowRight class="size-4" />
                        </Link>
                        <Link
                            href="/kontakt"
                            class="inline-flex items-center gap-2 rounded-lg border bg-white px-4 py-2.5 text-sm font-semibold text-[#1a3a5c] hover:text-[#1a6bbf]"
                        >
                            Beratung anfragen
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <AppFooter />
</div>
