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

    <section class="bg-[#f5f5f7]">
        <div class="mx-auto max-w-7xl px-4 py-18 lg:px-8 lg:py-24">
            <div class="max-w-3xl">
                <p class="mb-3 text-sm font-semibold uppercase tracking-[0.18em] text-[#1a6bbf]">
                    Beschaffung mit klarer Linie
                </p>
                <h2 class="max-w-2xl text-3xl font-semibold tracking-[-0.02em] text-[#0d1f44] lg:text-5xl">
                    Ein Shop, der wie eine echte medizinische Landingpage erklärt, wie Einkauf hier funktioniert.
                </h2>
                <p class="mt-5 max-w-2xl text-base leading-7 text-[#1a3a5c]/78 lg:text-lg">
                    Der Live-Stand von dormed24 setzt stark auf einfache Registrierung, schnelles Freischalten und direkte Produktverfügbarkeit. Genau darauf baut dieser Mittelteil auf.
                </p>
            </div>

            <div class="mt-12 grid gap-5 lg:grid-cols-3">
                {#each onboardingSteps as step, index}
                    <article class="rounded-[28px] bg-white p-7 shadow-[0_22px_60px_rgba(13,31,68,0.08)]">
                        <div class="mb-6 flex size-12 items-center justify-center rounded-full bg-[#0d1f44] text-sm font-semibold text-white">
                            0{index + 1}
                        </div>
                        <h3 class="text-xl font-semibold tracking-[-0.01em] text-[#0d1f44]">
                            {step.title}
                        </h3>
                        <p class="mt-3 text-sm leading-6 text-[#1a3a5c]/78">
                            {step.text}
                        </p>
                    </article>
                {/each}
            </div>
        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-4 py-18 lg:px-8 lg:py-24">
            <div class="grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-start">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#1a6bbf]">
                        Sortiment & Nachfrage
                    </p>
                    <h2 class="mt-3 max-w-xl text-3xl font-semibold tracking-[-0.02em] text-[#0d1f44] lg:text-5xl">
                        Neuheiten und Bedarfsträger sauber inszeniert, statt nur Produktlisten aneinanderzureihen.
                    </h2>
                    <p class="mt-5 max-w-xl text-base leading-7 text-[#1a3a5c]/78 lg:text-lg">
                        Auf dormed24.de stehen aktuell kompakte Diagnostik- und Notfallprodukte im Vordergrund. Für die neue Startseite übersetzen wir das in kuratierte Highlights und eine stärkere Vertrauensdramaturgie.
                    </p>

                    <div class="mt-8 grid gap-3">
                        {#each assortmentPillars as pillar}
                            <div class="flex items-start gap-3 rounded-full bg-[#f5f7fb] px-5 py-3 text-sm font-medium text-[#0d1f44]">
                                <Check class="mt-0.5 size-4 shrink-0 text-[#1a6bbf]" />
                                <span>{pillar}</span>
                            </div>
                        {/each}
                    </div>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link
                            href={ProductController.index.url()}
                            class="inline-flex items-center gap-2 rounded-full bg-[#0071e3] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#005fcc]"
                        >
                            Produkte ansehen
                            <ArrowRight class="size-4" />
                        </Link>
                        <Link
                            href="/kontakt"
                            class="inline-flex items-center gap-2 rounded-full border border-[#0d1f44]/12 px-5 py-3 text-sm font-semibold text-[#0d1f44] transition hover:border-[#1a6bbf] hover:text-[#1a6bbf]"
                        >
                            Beratung anfragen
                        </Link>
                    </div>
                </div>

                <div class="grid gap-4">
                    {#each highlightProducts as product}
                        <article class="rounded-[28px] border border-[#dfe7f1] bg-white p-6 shadow-[0_16px_40px_rgba(13,31,68,0.06)]">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#1a6bbf]">
                                Neu bei dormed24
                            </p>
                            <h3 class="mt-3 text-xl font-semibold tracking-[-0.01em] text-[#0d1f44]">
                                {product.title}
                            </h3>
                            <p class="mt-3 text-sm leading-6 text-[#1a3a5c]/78">
                                {product.summary}
                            </p>
                            <div class="mt-5 flex items-center justify-between gap-4 border-t border-[#e8edf5] pt-4">
                                <span class="text-lg font-semibold text-[#0d1f44]">{product.price}</span>
                                <Link
                                    href={ProductController.index.url()}
                                    class="inline-flex items-center gap-2 text-sm font-semibold text-[#1a6bbf] hover:text-[#0d1f44]"
                                >
                                    Zum Sortiment
                                    <ArrowRight class="size-4" />
                                </Link>
                            </div>
                        </article>
                    {/each}
                </div>
            </div>
        </div>
    </section>

    <section class="bg-[#0d1f44] text-white">
        <div class="mx-auto max-w-7xl px-4 py-18 lg:px-8 lg:py-24">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-white/60">
                    Landingpage-Mitte mit mehr Gewicht
                </p>
                <h2 class="mt-3 text-3xl font-semibold tracking-[-0.02em] lg:text-5xl">
                    Mehr Substanz zwischen Trust-Leiste und Footer, ohne den bestehenden Kopfbereich umzubauen.
                </h2>
            </div>

            <div class="mt-12 grid gap-5 lg:grid-cols-3">
                {#each serviceColumns as column}
                    <article class="rounded-[28px] bg-white/8 p-7 backdrop-blur-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#8ec7ff]">
                            {column.eyebrow}
                        </p>
                        <h3 class="mt-3 text-2xl font-semibold tracking-[-0.02em] text-white">
                            {column.title}
                        </h3>
                        <p class="mt-4 text-sm leading-6 text-white/72">
                            {column.text}
                        </p>
                    </article>
                {/each}
            </div>

            <div class="mt-12 rounded-[32px] bg-white px-6 py-8 text-[#0d1f44] shadow-[0_26px_80px_rgba(0,0,0,0.16)] lg:flex lg:items-center lg:justify-between lg:px-10">
                <div class="max-w-2xl">
                    <h3 class="text-2xl font-semibold tracking-[-0.02em] lg:text-3xl">
                        Sie brauchen mehr als nur einen Warenkorb.
                    </h3>
                    <p class="mt-3 text-sm leading-6 text-[#1a3a5c]/78 lg:text-base">
                        Dann sollte die Startseite das auch zeigen: mit Sortimentssignal, Beratungskontext und einem klaren Weg in den Shop oder direkt zum Kontakt.
                    </p>
                </div>
                <div class="mt-6 flex flex-wrap gap-3 lg:mt-0">
                    <Link
                        href={ProductController.index.url()}
                        class="inline-flex items-center gap-2 rounded-full bg-[#0d1f44] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#1a3a5c]"
                    >
                        Jetzt shoppen
                        <ArrowRight class="size-4" />
                    </Link>
                    <a
                        href="tel:023011886000"
                        class="inline-flex items-center gap-2 rounded-full border border-[#0d1f44]/12 px-5 py-3 text-sm font-semibold text-[#0d1f44] transition hover:border-[#1a6bbf] hover:text-[#1a6bbf]"
                    >
                        Anrufen
                    </a>
                </div>
            </div>
        </div>
    </section>

    <AppFooter />
</div>
