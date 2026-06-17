<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ChevronRight from 'lucide-svelte/icons/chevron-right';
    import Minus from 'lucide-svelte/icons/minus';
    import Plus from 'lucide-svelte/icons/plus';
    import ShoppingCart from 'lucide-svelte/icons/shopping-cart';
    import AppHead from '@/components/AppHead.svelte';
    import ShopHeader from '@/components/ShopHeader.svelte';
    import { Button } from '@/components/ui/button';
    import { Separator } from '@/components/ui/separator';
    import * as ProductController from '@/actions/App/Http/Controllers/ProductController';
    import { cn } from '@/lib/utils';

    type Product = {
        id: number;
        name: string;
        description: string | null;
        price: string;
        manufacturer: { id: number; name: string } | null;
    };

    let { product }: { product: Product } = $props();

    let quantity = $state(1);
    let activeTab = $state<'beschreibung' | 'bewertungen'>('beschreibung');

    function formatPrice(value: number | string): string {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: 'EUR',
        }).format(Number(value));
    }
</script>

<AppHead title={product.name} />

<div class="min-h-screen bg-white">
    <ShopHeader />

    <main class="mx-auto max-w-7xl px-4 py-6 lg:px-8">

        <!-- Breadcrumb -->
        <nav class="mb-6 flex items-center gap-1.5 text-sm text-gray-500">
            <Link href={ProductController.index.url()} class="hover:text-[#1a6bbf]">
                Alle Produkte
            </Link>
            {#if product.manufacturer}
                <ChevronRight class="size-3.5 shrink-0" />
                <span class="text-gray-400">{product.manufacturer.name}</span>
            {/if}
            <ChevronRight class="size-3.5 shrink-0" />
            <span class="truncate text-gray-800">{product.name}</span>
        </nav>

        <!-- Title row -->
        <div class="mb-6 flex items-start justify-between gap-6">
            <h1 class="text-2xl font-bold text-gray-900 lg:text-3xl">{product.name}</h1>
            {#if product.manufacturer}
                <span class="shrink-0 rounded border border-gray-200 px-3 py-1.5 text-sm font-semibold text-[#1a3a5c]">
                    {product.manufacturer.name}
                </span>
            {/if}
        </div>

        <!-- Two-column layout -->
        <div class="flex flex-col gap-10 lg:flex-row lg:items-start">

            <!-- Left: image -->
            <div class="w-full lg:max-w-lg xl:max-w-xl">
                <div class="flex aspect-square items-center justify-center rounded-xl border bg-gray-50">
                    <ShoppingCart class="size-20 text-gray-200" strokeWidth={1} />
                </div>
            </div>

            <!-- Right: purchase info -->
            <div class="flex-1">
                <!-- Price -->
                <div class="mb-1">
                    <span class="text-3xl font-bold text-gray-900">
                        {formatPrice(product.price)}*
                    </span>
                </div>
                <p class="mb-5 text-sm text-[#1a6bbf] hover:underline">
                    <a href="/versandkosten">Preise inkl. MwSt. zzgl. Versandkosten</a>
                </p>

                <!-- Availability -->
                <div class="mb-6 flex items-center gap-2">
                    <span class="size-2.5 shrink-0 rounded-full bg-green-500"></span>
                    <span class="text-sm text-gray-700">
                        Sofort verfügbar, Lieferzeit: 1–2 Wochen
                    </span>
                </div>

                <Separator class="mb-6" />

                <!-- Qty + CTA -->
                <div class="mb-6 flex items-center gap-3">
                    <div class="flex items-center rounded border">
                        <button
                            class="flex size-10 items-center justify-center text-gray-500 hover:bg-gray-50 disabled:opacity-40"
                            onclick={() => (quantity = Math.max(1, quantity - 1))}
                            disabled={quantity <= 1}
                            aria-label="Menge verringern"
                        >
                            <Minus class="size-4" />
                        </button>
                        <span class="w-12 text-center text-sm font-semibold">{quantity}</span>
                        <button
                            class="flex size-10 items-center justify-center text-gray-500 hover:bg-gray-50"
                            onclick={() => quantity++}
                            aria-label="Menge erhöhen"
                        >
                            <Plus class="size-4" />
                        </button>
                    </div>

                    <Button class="flex-1 bg-[#0d1f44] text-white hover:bg-[#0d1f44]/90">
                        <ShoppingCart class="size-4" />
                        In den Warenkorb
                    </Button>
                </div>

                <!-- Product number -->
                <p class="text-sm text-gray-500">
                    <span class="font-semibold text-gray-700">Produktnummer:</span>
                    #{product.id}
                </p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mt-12">
            <div class="flex border-b">
                <button
                    class={cn(
                        'px-6 py-3 text-sm font-semibold transition-colors',
                        activeTab === 'beschreibung'
                            ? 'border-b-2 border-[#1a6bbf] text-[#1a6bbf]'
                            : 'text-gray-500 hover:text-gray-800',
                    )}
                    onclick={() => (activeTab = 'beschreibung')}
                >
                    Beschreibung
                </button>
                <button
                    class={cn(
                        'px-6 py-3 text-sm font-semibold transition-colors',
                        activeTab === 'bewertungen'
                            ? 'border-b-2 border-[#1a6bbf] text-[#1a6bbf]'
                            : 'text-gray-500 hover:text-gray-800',
                    )}
                    onclick={() => (activeTab = 'bewertungen')}
                >
                    Bewertungen
                </button>
            </div>

            <div class="py-8">
                {#if activeTab === 'beschreibung'}
                    <div class="max-w-3xl">
                        <h2 class="mb-4 text-lg font-bold text-gray-900">
                            Produktinformationen „{product.name}"
                        </h2>
                        {#if product.description}
                            <div class="prose prose-sm max-w-none text-gray-700">
                                {@html product.description}
                            </div>
                        {:else}
                            <p class="text-sm text-gray-400">
                                Keine Beschreibung vorhanden.
                            </p>
                        {/if}
                    </div>
                {:else}
                    <p class="text-sm text-gray-400">Noch keine Bewertungen vorhanden.</p>
                {/if}
            </div>
        </div>
    </main>
</div>
