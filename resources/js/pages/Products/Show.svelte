<script lang="ts">
    import { Form, Link, router } from '@inertiajs/svelte';
    import ChevronRight from 'lucide-svelte/icons/chevron-right';
    import Minus from 'lucide-svelte/icons/minus';
    import Plus from 'lucide-svelte/icons/plus';
    import ShoppingCart from 'lucide-svelte/icons/shopping-cart';
    import Star from 'lucide-svelte/icons/star';
    import * as CategoryController from '@/actions/App/Http/Controllers/CategoryController';
    import * as ProductController from '@/actions/App/Http/Controllers/ProductController';
    import AppFooter from '@/components/AppFooter.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import ShopHeader from '@/components/ShopHeader.svelte';
    import { Button } from '@/components/ui/button';
    import * as Carousel from '@/components/ui/carousel';
    import { Label } from '@/components/ui/label';
    import * as Select from '@/components/ui/select';
    import { Textarea } from '@/components/ui/textarea';
    import { formatPrice } from '@/lib/currency';
    import { cn } from '@/lib/utils';
    import cartRoutes from '@/routes/cart';
    import ratingsRoutes from '@/routes/ratings';

    type ProductImage = { id: number; url: string; sort_order: number };
    type ProductVariant = {
        id: number;
        label: string;
        price: string;
        is_default: boolean;
    };

    type Product = {
        id: number;
        name: string;
        description: string | null;
        price: string;
        is_available: boolean;
        category: { id: number; name: string; slug: string } | null;
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

    // svelte-ignore state_referenced_locally
    const hasVariants = product.variants.length > 0;
    // svelte-ignore state_referenced_locally
    const defaultVariant =
        product.variants.find((v) => v.is_default) ??
        product.variants[0] ??
        null;

    let selectedVariantValue = $state(
        defaultVariant ? String(defaultVariant.id) : '',
    );
    let quantity = $state(1);
    let activeTab = $state<'beschreibung' | 'bewertungen'>('beschreibung');
    let ratingStars = $state(5);
    let activeImageIndex = $state(0);
    let carouselApi = $state<any>();
    let zoomActive = $state(false);
    let cursorPct = $state({ x: 0.5, y: 0.5 });
    let zoomPanel = $state<{
        left: number;
        top: number;
        width: number;
        height: number;
    } | null>(null);

    function handleZoomMouseEnter(e: MouseEvent) {
        const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
        const left = rect.right + 24;
        const width = Math.max(320, window.innerWidth - left - 32);
        zoomPanel = { left, top: rect.top, width, height: rect.height };
        zoomActive = true;
    }

    function handleZoomMouseLeave() {
        zoomActive = false;
    }

    function handleZoomMouseMove(e: MouseEvent) {
        const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
        cursorPct = {
            x: Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width)),
            y: Math.max(0, Math.min(1, (e.clientY - rect.top) / rect.height)),
        };
    }

    const selectedVariant = $derived(
        hasVariants
            ? (product.variants.find(
                  (v) => String(v.id) === selectedVariantValue,
              ) ?? null)
            : null,
    );
    const displayedPrice = $derived(
        selectedVariant ? selectedVariant.price : product.price,
    );
    const ratingAverageValue = $derived(
        ratingSummary.average !== null
            ? Number(ratingSummary.average.replace(',', '.'))
            : null,
    );
    const variantSelectLabel = $derived(
        selectedVariant?.label ?? 'Variation waehlen',
    );

    const metaDescription = $derived(
        product.description
            ? product.description.replace(/\n+/g, ' ').trim().slice(0, 160)
            : null,
    );

    const schemaAvailability = $derived(
        product.is_available
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock',
    );

    const productUrl = $derived.by(() => {
        const path = ProductController.show.url(product.id);

        return typeof window === 'undefined'
            ? path
            : new URL(path, window.location.origin).href;
    });

    const productSchema = $derived.by(() => {
        const schema: Record<string, unknown> = {
            '@context': 'https://schema.org/',
            '@type': 'Product',
            name: product.name,
            sku: String(product.id),
            url: productUrl,
            ...(product.description
                ? { description: product.description }
                : {}),
            ...(product.images.length > 0
                ? { image: product.images.map((img) => img.url) }
                : {}),
            ...(product.manufacturer
                ? {
                      brand: {
                          '@type': 'Brand',
                          name: product.manufacturer.name,
                      },
                  }
                : {}),
            ...(ratingAverageValue !== null && ratingSummary.count > 0
                ? {
                      aggregateRating: {
                          '@type': 'AggregateRating',
                          ratingValue: ratingAverageValue,
                          reviewCount: ratingSummary.count,
                          bestRating: 5,
                          worstRating: 1,
                      },
                  }
                : {}),
        };

        if (hasVariants && product.variants.length > 0) {
            const prices = product.variants.map((v) => parseFloat(v.price));
            schema.offers = {
                '@type': 'AggregateOffer',
                priceCurrency: 'EUR',
                lowPrice: Math.min(...prices).toFixed(2),
                highPrice: Math.max(...prices).toFixed(2),
                offerCount: product.variants.length,
                offers: product.variants.map((v) => ({
                    '@type': 'Offer',
                    name: v.label,
                    price: parseFloat(v.price).toFixed(2),
                    priceCurrency: 'EUR',
                    availability: schemaAvailability,
                    itemCondition: 'https://schema.org/NewCondition',
                })),
            };
        } else {
            schema.offers = {
                '@type': 'Offer',
                price: parseFloat(product.price).toFixed(2),
                priceCurrency: 'EUR',
                availability: schemaAvailability,
                itemCondition: 'https://schema.org/NewCondition',
            };
        }

        // "<" escaped so admin-supplied content (name/description) can never
        // close the surrounding script tag early (script-breakout injection).
        return JSON.stringify(schema).replace(/</g, '\\u003c');
    });

    const jsonLdHtml = $derived(
        `<script type="application/ld+json">${productSchema}<` + `/script>`,
    );

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

    $effect(() => {
        if (!carouselApi || product.images.length <= 1) {
            return;
        }

        const syncFromCarousel = () => {
            activeImageIndex = carouselApi.selectedScrollSnap();
        };

        carouselApi.on('select', syncFromCarousel);
        syncFromCarousel();

        return () => {
            carouselApi.off('select', syncFromCarousel);
        };
    });

    $effect(() => {
        if (!carouselApi) {
            return;
        }

        if (carouselApi.selectedScrollSnap() === activeImageIndex) {
            return;
        }

        carouselApi.scrollTo(activeImageIndex);
    });
</script>

<AppHead
    title={product.name}
    description={metaDescription}
    ogImage={product.images[0]?.url ?? null}
    ogType="product"
/>

<svelte:head>
    <!-- eslint-disable-next-line svelte/no-at-html-tags -- JSON-LD; "<" is <-escaped in productSchema -->
    {@html jsonLdHtml}
</svelte:head>

<div class="flex min-h-screen flex-col bg-white">
    <ShopHeader />

    <main class="mx-auto flex-1 max-w-7xl px-4 py-6 lg:px-8">
        <nav class="mb-8 flex items-center gap-1.5 text-sm text-gray-500">
            <Link
                href={ProductController.index.url()}
                class="hover:text-[#1a6bbf]"
            >
                Alle Produkte
            </Link>
            {#if product.category}
                <ChevronRight class="size-3.5 shrink-0" />
                <Link
                    href={CategoryController.show.url(product.category.slug)}
                    class="text-gray-600 hover:text-[#1a6bbf]"
                >
                    {product.category.name}
                </Link>
            {/if}
            <ChevronRight class="size-3.5 shrink-0" />
            <span class="truncate text-gray-800">{product.name}</span>
        </nav>

        <div class="grid gap-12 lg:grid-cols-2 lg:items-start">
            <div class="w-full">
                {#if product.images.length > 0}
                    <div class="flex flex-col gap-4">
                        <Carousel.Root
                            class="w-full"
                            setApi={(api) => {
                                carouselApi = api;
                            }}
                        >
                            <Carousel.Content class="-ms-0">
                                {#each product.images as image (image.id)}
                                    <Carousel.Item class="ps-0">
                                        <div
                                            class="aspect-square w-full cursor-crosshair overflow-hidden rounded-[1.75rem] border border-gray-200 bg-[#f6f7f8]"
                                            onmouseenter={handleZoomMouseEnter}
                                            onmouseleave={handleZoomMouseLeave}
                                            onmousemove={handleZoomMouseMove}
                                            role="none"
                                        >
                                            <img
                                                src={image.url}
                                                alt={product.name}
                                                class="size-full object-cover"
                                            />
                                        </div>
                                    </Carousel.Item>
                                {/each}
                            </Carousel.Content>
                        </Carousel.Root>

                        {#if product.images.length > 1}
                            <div class="grid grid-cols-5 gap-3">
                                {#each product.images as image, i (image.id)}
                                    <button
                                        type="button"
                                        onclick={() => (activeImageIndex = i)}
                                        class={cn(
                                            'aspect-square overflow-hidden rounded-2xl border bg-[#f6f7f8] transition-colors',
                                            activeImageIndex === i
                                                ? 'border-[#0d1f44]'
                                                : 'border-gray-200 hover:border-gray-300',
                                        )}
                                        aria-label={`Bild ${i + 1}`}
                                    >
                                        <img
                                            src={image.url}
                                            alt=""
                                            class="size-full object-cover"
                                        />
                                    </button>
                                {/each}
                            </div>
                        {/if}
                    </div>
                {:else}
                    <div
                        class="flex aspect-square items-center justify-center rounded-[1.75rem] border border-gray-200 bg-[#f6f7f8]"
                    >
                        <ShoppingCart
                            class="size-20 text-gray-200"
                            strokeWidth={1}
                        />
                    </div>
                {/if}
            </div>

            <div class="flex flex-col gap-8">
                <div class="flex flex-col gap-5">
                    <div class="flex flex-col gap-3">
                        {#if product.manufacturer}
                            <span
                                class="text-sm font-medium uppercase tracking-[0.18em] text-[#1a6bbf]"
                            >
                                {product.manufacturer.name}
                            </span>
                        {/if}
                        <h1
                            class="max-w-xl text-3xl font-semibold tracking-tight text-[#111827] lg:text-5xl"
                        >
                            {product.name}
                        </h1>
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-3 text-sm text-gray-600"
                    >
                        <div class="flex items-center gap-1 text-[#111827]">
                            {#each Array.from({ length: 5 }, (_, index) => index + 1) as star (star)}
                                <Star
                                    class={cn(
                                        'size-4',
                                        ratingAverageValue !== null &&
                                            star <=
                                                Math.round(ratingAverageValue)
                                            ? 'fill-current'
                                            : 'text-gray-300',
                                    )}
                                />
                            {/each}
                        </div>
                        <span>
                            {ratingSummary.count > 0
                                ? `${ratingSummary.count} Bewertung${ratingSummary.count === 1 ? '' : 'en'}`
                                : 'Noch keine Bewertungen'}
                        </span>
                    </div>

                    {#if product.description}
                        <p
                            class="max-w-2xl whitespace-pre-line text-base leading-8 text-gray-600"
                        >
                            {product.description}
                        </p>
                    {/if}

                    <div class="flex flex-wrap items-end gap-4">
                        <span
                            class="text-4xl font-semibold tracking-tight text-[#111827]"
                        >
                            {formatPrice(displayedPrice)}*
                        </span>
                    </div>

                    <p class="text-sm text-[#1a6bbf] hover:underline">
                        <a href="/versandkosten"
                            >Preise inkl. MwSt. zzgl. Versandkosten</a
                        >
                    </p>
                </div>

                <div class="flex flex-col gap-6">
                    {#if hasVariants}
                        <div class="flex flex-col gap-2.5">
                            <Label class="text-sm font-medium text-[#111827]"
                                >Variation</Label
                            >
                            <Select.Root
                                type="single"
                                bind:value={selectedVariantValue}
                            >
                                <Select.Trigger
                                    class="h-12 w-full rounded-xl border-gray-300 text-left text-[15px]"
                                >
                                    {variantSelectLabel}
                                </Select.Trigger>
                                <Select.Content>
                                    {#each product.variants as variant (variant.id)}
                                        <Select.Item value={String(variant.id)}>
                                            {variant.label}
                                        </Select.Item>
                                    {/each}
                                </Select.Content>
                            </Select.Root>
                        </div>
                    {/if}

                    <div class="flex items-center gap-2">
                        <span
                            class="size-2.5 shrink-0 rounded-full {product.is_available
                                ? 'bg-emerald-500'
                                : 'bg-red-400'}"
                        ></span>
                        <span class="text-sm text-gray-700">
                            {product.is_available
                                ? 'Sofort verfügbar, Lieferzeit: 1-2 Wochen'
                                : 'Derzeit nicht verfügbar'}
                        </span>
                    </div>

                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center"
                    >
                        <div
                            class="flex h-12 items-center rounded-xl border border-gray-300 bg-white"
                        >
                            <button
                                class="flex h-full w-12 items-center justify-center text-gray-500 hover:bg-gray-50 disabled:opacity-40"
                                onclick={() =>
                                    (quantity = Math.max(1, quantity - 1))}
                                disabled={quantity <= 1}
                                aria-label="Menge verringern"
                            >
                                <Minus class="size-4" />
                            </button>
                            <span class="w-14 text-center text-sm font-semibold"
                                >{quantity}</span
                            >
                            <button
                                class="flex h-full w-12 items-center justify-center text-gray-500 hover:bg-gray-50"
                                onclick={() => quantity++}
                                aria-label="Menge erhöhen"
                            >
                                <Plus class="size-4" />
                            </button>
                        </div>

                        <Button
                            class="h-12 flex-1 rounded-xl bg-[#111111] text-white hover:bg-[#111111]/90 disabled:opacity-50"
                            onclick={addToCart}
                            disabled={!product.is_available}
                        >
                            <ShoppingCart class="size-4" />
                            {product.is_available
                                ? 'In den Warenkorb'
                                : 'Nicht verfügbar'}
                        </Button>
                    </div>
                </div>
            </div>
        </div>

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
                    Bewertungen {ratingSummary.count > 0
                        ? `(${ratingSummary.count})`
                        : ''}
                </button>
            </div>

            <div class="py-8">
                {#if activeTab === 'beschreibung'}
                    <div class="max-w-3xl">
                        <h2 class="mb-4 text-lg font-bold text-gray-900">
                            Produktinformationen "{product.name}"
                        </h2>
                        {#if product.description}
                            <p
                                class="whitespace-pre-line text-sm leading-relaxed text-gray-700"
                            >
                                {product.description}
                            </p>
                        {:else}
                            <p class="text-sm text-gray-400">
                                Keine Beschreibung vorhanden.
                            </p>
                        {/if}
                    </div>
                {:else}
                    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_24rem]">
                        <div class="space-y-6">
                            <div
                                class="rounded-xl border border-gray-200 bg-white p-6"
                            >
                                <div
                                    class="flex flex-wrap items-end justify-between gap-4"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-semibold uppercase tracking-wide text-[#1a6bbf]"
                                        >
                                            Kundenbewertungen
                                        </p>
                                        <div class="mt-2 flex items-end gap-3">
                                            <span
                                                class="text-3xl font-bold text-gray-900"
                                            >
                                                {ratingSummary.average ?? '-'}
                                            </span>
                                            <span
                                                class="pb-1 text-sm text-gray-500"
                                            >
                                                von 5 bei {ratingSummary.count} Bewertung{ratingSummary.count ===
                                                1
                                                    ? ''
                                                    : 'en'}
                                            </span>
                                        </div>
                                    </div>
                                    <div
                                        class="flex items-center gap-1 text-[#f59e0b]"
                                    >
                                        {#each Array.from({ length: 5 }, (_, index) => index + 1) as star (star)}
                                            <Star
                                                class={cn(
                                                    'size-5',
                                                    ratingAverageValue !==
                                                        null &&
                                                        star <=
                                                            Math.round(
                                                                ratingAverageValue,
                                                            )
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
                                        <article
                                            class="rounded-xl border border-gray-200 bg-white p-6"
                                        >
                                            <div
                                                class="mb-3 flex items-center justify-between gap-4"
                                            >
                                                <div
                                                    class="flex items-center gap-1 text-[#f59e0b]"
                                                >
                                                    {#each Array.from({ length: 5 }, (_, index) => index + 1) as star (star)}
                                                        <Star
                                                            class={cn(
                                                                'size-4',
                                                                star <=
                                                                    rating.stars
                                                                    ? 'fill-current'
                                                                    : 'text-gray-300',
                                                            )}
                                                        />
                                                    {/each}
                                                </div>
                                                {#if rating.created_at}
                                                    <span
                                                        class="text-sm text-gray-500"
                                                        >{rating.created_at}</span
                                                    >
                                                {/if}
                                            </div>
                                            <p
                                                class="text-sm leading-6 text-gray-700"
                                            >
                                                {rating.content}
                                            </p>
                                        </article>
                                    {/each}
                                </div>
                            {:else}
                                <div
                                    class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-gray-500"
                                >
                                    Noch keine Bewertungen vorhanden.
                                </div>
                            {/if}
                        </div>

                        <div
                            class="rounded-xl border border-gray-200 bg-white p-6"
                        >
                            <h2 class="text-lg font-bold text-gray-900">
                                Bewertung abgeben
                            </h2>
                            <p class="mt-2 text-sm text-gray-500">
                                Ohne Login. Später kann die Moderation oder
                                Verknüpfung zu Kunden ergänzt werden.
                            </p>

                            <Form
                                action={ratingsRoutes.store.url(product.id)}
                                method="post"
                                resetOnSuccess={['content']}
                                class="mt-6 space-y-5"
                            >
                                {#snippet children({ errors, processing })}
                                    <input
                                        type="hidden"
                                        name="stars"
                                        value={ratingStars}
                                    />

                                    <div class="grid gap-2">
                                        <Label for="rating-stars">Sterne</Label>
                                        <div
                                            id="rating-stars"
                                            class="star-wrapper"
                                        >
                                            {#each [1, 2, 3, 4, 5] as star (star)}
                                                <button
                                                    type="button"
                                                    class={cn(
                                                        `star-button s${star}`,
                                                        star <= ratingStars &&
                                                            'active',
                                                    )}
                                                    onclick={() =>
                                                        (ratingStars = star)}
                                                    aria-label={starLabel(star)}
                                                    aria-pressed={star ===
                                                        ratingStars}
                                                >
                                                    <Star class="size-12" />
                                                </button>
                                            {/each}
                                        </div>
                                        <p class="text-sm text-gray-500">
                                            {starLabel(ratingStars)}
                                        </p>
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

{#if zoomActive && zoomPanel && product.images[activeImageIndex]}
    <div
        class="pointer-events-none fixed z-50 hidden overflow-hidden rounded-xl border bg-gray-50 shadow-2xl lg:block"
        style="left: {zoomPanel.left}px; top: {zoomPanel.top}px; width: {zoomPanel.width}px; height: {zoomPanel.height}px; background-image: url('{product
            .images[activeImageIndex]
            .url}'); background-size: 280%; background-position: {cursorPct.x *
            100}% {cursorPct.y * 100}%; background-repeat: no-repeat;"
    ></div>
{/if}

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
