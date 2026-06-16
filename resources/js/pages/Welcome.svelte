<script lang="ts">
    import Check from 'lucide-svelte/icons/check';
    import Phone from 'lucide-svelte/icons/phone';
    import AppHead from '@/components/AppHead.svelte';
    import ShopHeader from '@/components/ShopHeader.svelte';

    const features = [
        'Diagnostik',
        'Notfallmedizin',
        'Monitoring',
        'Zubehör & Verbrauchsmaterial',
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

                <ul class="mb-10 space-y-2.5">
                    {#each features as feature}
                        <li class="flex items-center gap-2.5 font-semibold text-white">
                            <Check class="h-4 w-4 shrink-0" />
                            {feature}
                        </li>
                    {/each}
                </ul>

                <a
                    href="tel:023011886000"
                    class="group inline-flex items-center gap-4 rounded-xl bg-white/10 px-5 py-3.5 backdrop-blur-sm transition hover:bg-white/20"
                >
                    <div class="rounded-full bg-white/20 p-2.5 transition group-hover:bg-white/30">
                        <Phone class="h-5 w-5 text-white" />
                    </div>
                    <span class="text-2xl font-bold tracking-wide text-white lg:text-3xl">
                        02301 – 188/600
                    </span>
                </a>
            </div>
        </div>
    </section>
</div>
