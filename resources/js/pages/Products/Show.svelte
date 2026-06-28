<script lang="ts">
    import { Form, Link, router } from '@inertiajs/svelte';
    import ChevronRight from 'lucide-svelte/icons/chevron-right';
    import Minus from 'lucide-svelte/icons/minus';
    import Plus from 'lucide-svelte/icons/plus';
    import Star from 'lucide-svelte/icons/star';
    import ShoppingCart from 'lucide-svelte/icons/shopping-cart';
    import AppFooter from '@/components/AppFooter.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import ShopHeader from '@/components/ShopHeader.svelte';
    import cartRoutes from '@/routes/cart';
    import ratingsRoutes from '@/routes/ratings';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { Textarea } from '@/components/ui/textarea';
    import { Separator } from '@/components/ui/separator';
    import * as ProductController from '@/actions/App/Http/Controllers/ProductController';
    import { formatPrice } from '@/lib/currency';
    import { cn } from '@/lib/utils';

    type ProductImage = { id: number; url: string; sort_order: number };
    type ProductVariant = { id: number; label: string; price: string; is_default: boolean };

    type Product = {
        id: number;
        name: string;
        description: string | null;
        price: string;
        manufacturer: { id: number; name: string } | null;
        images: ProductImage[];
        variants: ProductVariant[];
    };

    type Rating = {
        id: number;
        stars: number;
        content: string;
        created_at: string | null;
    };

    type RatingSummary = {
        average: string | null;
        count: number;
    };

    let {
        product,
        ratings,
        ratingSummary,
    }: {
        product: Product;
        ratings: Rating[];
        ratingSummary: RatingSummary;
    } = $props();

    const hasVariants = product.variants.length > 0;
    const defaultVariant = product.variants.find((v) => v.is_default) ?? product.variants[0] ?? null;

    let selectedVariantId = $state<number | null>(defaultVariant?.id ?? null);
    let quantity = $state(1);
    let activeTab = $state<'beschreibung' | 'bewertungen'>('beschreibung');
    let ratingStars = $state(5);
    let activeImageIndex = $state(0);

    const selectedVariant = $derived(
        hasVariants ? product.variants.find((v) => v.id === selectedVariantId) ?? null : null,
    );
    const displayedPrice = $derived(selectedVariant ? selectedVariant.price : product.price);

    function starLabel(stars: number): string {
        return `${stars} Stern${stars === 1 ? '' : 'e'}`;
    }

    function addToCart() {
        router.post(
            cartRoutes.items.store.url(),
            {
                product_id: product.id,
                quantity,
            },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
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

        <!-- Two-column layout -->
        <div class="flex flex-col gap-10 lg:flex-row lg:items-start">

            <!-- Left: image gallery -->
            <div class="w-full lg:max-w-lg xl:max-w-xl">
                {#if product.images.length > 0}
                    <div class="flex flex-col gap-3">
                        <!-- Main image -->
                        <div class="aspect-square w-full overflow-hidden rounded-xl border bg-gray-50">
                            <img
                                src={product.images[activeImageIndex]?.url}
                                alt={product.name}
                                class="size-full object-cover"
                            />
                        </div>
                        <!-- Thumbnails -->
                        {#if product.images.length > 1}
                            <div class="flex gap-2">
                                {#each product.images as image, i (image.id)}
                                    <button
                                        type="button"
                                        onclick={() => (activeImageIndex = i)}
                                        class={cn(
                                            'size-16 shrink-0 overflow-hidden rounded-lg border-2 bg-gray-50 transition-colors',
                                            activeImageIndex === i
                                                ? 'border-[#1a6bbf]'
                                                : 'border-transparent hover:border-gray-300',
                                        )}
                                        aria-label={`Bild ${i + 1}`}
                                    >
                                        <img src={image.url} alt="" class="size-full object-cover" />
                                    </button>
                                {/each}
                            </div>
                        {/if}
                    </div>
                {:else}
                    <div class="flex aspect-square items-center justify-center rounded-xl border bg-gray-50">
                        <ShoppingCart class="size-20 text-gray-200" strokeWidth={1} />
                    </div>
                {/if}
            </div>

            <!-- Right: purchase info -->
            <div class="flex-1">
                <!-- Name + manufacturer -->
                <div class="mb-4 flex items-start justify-between gap-4">
                    <h1 class="text-2xl font-bold text-gray-900 lg:text-3xl">{product.name}</h1>
                    {#if product.manufacturer}
                        <span class="shrink-0 rounded border border-gray-200 px-3 py-1.5 text-sm font-semibold text-[#1a3a5c]">
                            {product.manufacturer.name}
                        </span>
                    {/if}
                </div>

                <!-- Price -->
                <div class="mb-1">
                    <span class="text-3xl font-bold text-gray-900">
                        {formatPrice(displayedPrice)}*
                    </span>
                </div>
                <p class="mb-5 text-sm text-[#1a6bbf] hover:underline">
                    <a href="/versandkosten">Preise inkl. MwSt. zzgl. Versandkosten</a>
                </p>

                <!-- Short description -->
                {#if product.description}
                    <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                    <p class="mb-5 text-sm leading-relaxed text-gray-600">{@html product.description}</p>
                {/if}

                <Separator class="mb-6" />

                <!-- Variant picker -->
                {#if hasVariants}
                    <div class="mb-5">
                        <p class="mb-2 text-sm font-semibold text-gray-700">Verpackungseinheit</p>
                        <div class="flex flex-wrap gap-2">
                            {#each product.variants as variant (variant.id)}
                                <button
                                    type="button"
                                    onclick={() => (selectedVariantId = variant.id)}
                                    class={cn(
                                        'rounded border px-4 py-1.5 text-sm font-medium transition-colors',
                                        selectedVariantId === variant.id
                                            ? 'border-[#0d1f44] bg-[#0d1f44] text-white'
                                            : 'border-gray-300 text-gray-700 hover:border-[#0d1f44]',
                                    )}
                                >
                                    {variant.label}
                                </button>
                            {/each}
                        </div>
                    </div>
                {/if}

                <!-- Availability -->
                <div class="mb-5 flex items-center gap-2">
                    <span class="size-2.5 shrink-0 rounded-full bg-green-500"></span>
                    <span class="text-sm text-gray-700">Sofort verfügbar, Lieferzeit: 1–2 Wochen</span>
                </div>

                <!-- Qty + CTA -->
                <div class="flex items-center gap-3">
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

                    <Button
                        class="flex-1 bg-[#0d1f44] text-white hover:bg-[#0d1f44]/90"
                        onclick={addToCart}
                    >
                        <ShoppingCart class="size-4" />
                        In den Warenkorb
                    </Button>
                </div>
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
                    Bewertungen {ratingSummary.count > 0 ? `(${ratingSummary.count})` : ''}
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
                                <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                                {@html product.description}
                            </div>
                        {:else}
                            <p class="text-sm text-gray-400">
                                Keine Beschreibung vorhanden.
                            </p>
                        {/if}
                    </div>
                {:else}
                    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_24rem]">
                        <div class="space-y-6">
                            <div class="rounded-xl border border-gray-200 bg-white p-6">
                                <div class="flex flex-wrap items-end justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-semibold uppercase tracking-wide text-[#1a6bbf]">
                                            Kundenbewertungen
                                        </p>
                                        <div class="mt-2 flex items-end gap-3">
                                            <span class="text-3xl font-bold text-gray-900">
                                                {ratingSummary.average ?? '–'}
                                            </span>
                                            <span class="pb-1 text-sm text-gray-500">
                                                von 5 bei {ratingSummary.count} Bewertung{ratingSummary.count === 1 ? '' : 'en'}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 text-[#f59e0b]">
                                        {#each Array.from({ length: 5 }, (_, index) => index + 1) as star (star)}
                                            <Star
                                                class={cn(
                                                    'size-5',
                                                    ratingSummary.average !== null && star <= Math.round(Number(ratingSummary.average.replace(',', '.')))
                                                        ? 'fill-current'
                                                        : 'text-gray-300',
                                                )}
                                            />
                                        {/each}
                                    </div>
                                </div>
                            </div>

                            {#if ratings.length > 0}
                                <div class="space-y-4">
                                    {#each ratings as rating (rating.id)}
                                        <article class="rounded-xl border border-gray-200 bg-white p-6">
                                            <div class="mb-3 flex items-center justify-between gap-4">
                                                <div class="flex items-center gap-1 text-[#f59e0b]">
                                                    {#each Array.from({ length: 5 }, (_, index) => index + 1) as star (star)}
                                                        <Star
                                                            class={cn(
                                                                'size-4',
                                                                star <= rating.stars ? 'fill-current' : 'text-gray-300',
                                                            )}
                                                        />
                                                    {/each}
                                                </div>
                                                {#if rating.created_at}
                                                    <span class="text-sm text-gray-500">{rating.created_at}</span>
                                                {/if}
                                            </div>
                                            <p class="text-sm leading-6 text-gray-700">{rating.content}</p>
                                        </article>
                                    {/each}
                                </div>
                            {:else}
                                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-gray-500">
                                    Noch keine Bewertungen vorhanden.
                                </div>
                            {/if}
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-6">
                            <h2 class="text-lg font-bold text-gray-900">Bewertung abgeben</h2>
                            <p class="mt-2 text-sm text-gray-500">
                                Ohne Login. Später kann die Moderation oder Verknüpfung zu Kunden ergänzt werden.
                            </p>

                            <Form
                                action={ratingsRoutes.store.url(product.id)}
                                method="post"
                                resetOnSuccess={['content']}
                                class="mt-6 space-y-5"
                            >
                                {#snippet children({ errors, processing })}
                                    <input type="hidden" name="stars" value={ratingStars} />

                                    <div class="grid gap-2">
                                        <Label for="rating-stars">Sterne</Label>
                                        <div id="rating-stars" class="star-wrapper">
                                            {#each [1, 2, 3, 4, 5] as star (star)}
                                                <button
                                                    type="button"
                                                    class={cn(
                                                        `star-button s${star}`,
                                                        star <= ratingStars && 'active',
                                                    )}
                                                    onclick={() => (ratingStars = star)}
                                                    aria-label={starLabel(star)}
                                                    aria-pressed={star === ratingStars}
                                                >
                                                    <Star class="size-12" />
                                                </button>
                                            {/each}
                                        </div>
                                        <p class="text-sm text-gray-500">{starLabel(ratingStars)}</p>
                                        <InputError message={errors.stars} />
                                    </div>

                                    <div class="grid gap-2">
                                        <Label for="content">Bewertung</Label>
                                        <Textarea
                                            id="content"
                                            name="content"
                                            rows={5}
                                            required
                                            placeholder="Wie zufrieden sind Sie mit dem Produkt?"
                                        />
                                        <InputError message={errors.content} />
                                    </div>

                                    <Button
                                        type="submit"
                                        class="w-full bg-[#0d1f44] text-white hover:bg-[#0d1f44]/90"
                                        disabled={processing}
                                    >
                                        Bewertung senden
                                    </Button>
                                {/snippet}
                            </Form>
                        </div>
                    </div>
                {/if}
            </div>
        </div>
    </main>

    <AppFooter />
</div>

<style>
    .star-wrapper {
        display: inline-flex;
        direction: rtl;
    }

    .star-button {
        margin: 4px;
        color: #d1d5db;
        text-decoration: none;
        transition: all 0.5s;
    }

    .star-button:hover {
        color: gold;
        transform: scale(1.3);
    }

    .star-button:hover ~ .star-button {
        color: gold;
    }

    .star-button.active {
        color: gold;
    }
</style>
