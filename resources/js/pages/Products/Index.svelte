<script lang="ts">
    import { InfiniteScroll, Link, router } from '@inertiajs/svelte';
    import * as ProductController from '@/actions/App/Http/Controllers/ProductController';
    import AppFooter from '@/components/AppFooter.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ShopHeader from '@/components/ShopHeader.svelte';
    import { formatPrice } from '@/lib/currency';

    type ProductImage = { id: number; url: string; sort_order: number };

    type Product = {
        id: number;
        name: string;
        price: string;
        description: string | null;
        manufacturer: { id: number; name: string } | null;
        images: ProductImage[];
    };

    let {
        products,
        total,
        query,
        sort = 'name_asc',
    }: { products: { data: Product[] }; total: number; query: string; sort: string } = $props();

    const sortOptions = [
        { value: 'name_asc', label: 'Name A-Z' },
        { value: 'name_desc', label: 'Name Z-A' },
        { value: 'price_asc', label: 'Preis aufsteigend' },
        { value: 'price_desc', label: 'Preis absteigend' },
    ];

    function onSortChange(value: string) {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', value);
        url.searchParams.delete('page');
        router.visit(url.toString(), { preserveScroll: false });
    }
</script>

<AppHead
    title={query ? `Suchergebnisse für „${query}"` : 'Produkte'}
    description={query
        ? `Suchergebnisse für „${query}" im dormed24-Sortiment – Medizintechnik für Praxis und Klinik.`
        : 'Unser gesamtes Sortiment an Medizintechnik, Diagnostik, Monitoring und Verbrauchsmaterial – direkt online bestellen.'}
/>

<div class="flex min-h-screen flex-col bg-gray-50">
    <ShopHeader />

    <main class="mx-auto flex-1 max-w-7xl px-4 py-8 lg:px-8">
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                {#if query}
                    <h1 class="text-xl font-semibold text-gray-900">
                        Suchergebnisse für „{query}"
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {total}
                        {total === 1 ? 'Ergebnis' : 'Ergebnisse'}
                    </p>
                {:else}
                    <h1 class="text-xl font-semibold text-gray-900">Alle Produkte</h1>
                    <p class="mt-1 text-sm text-muted-foreground">{total} Produkte</p>
                {/if}
            </div>
            <select
                value={sort}
                onchange={(e) => onSortChange(e.currentTarget.value)}
                class="rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-[#0d1f44] focus:outline-none"
            >
                {#each sortOptions as option (option.value)}
                    <option value={option.value}>{option.label}</option>
                {/each}
            </select>
        </div>

        {#if products.data.length === 0}
            <div class="py-16 text-center">
                <p class="text-muted-foreground">
                    Keine Produkte gefunden{query ? ` für „${query}"` : ''}.
                </p>
                {#if query}
                    <Link
                        href={ProductController.index.url()}
                        class="mt-4 inline-block text-sm text-[#1a6bbf] hover:underline"
                    >
                        Alle Produkte anzeigen
                    </Link>
                {/if}
            </div>
        {:else}
            <InfiniteScroll data="products">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    {#each products.data as product (product.id)}
                        <Link
                            href={ProductController.show.url(product.id)}
                            class="group rounded-lg border bg-white p-3 shadow-sm transition hover:shadow-md"
                        >
                            <div
                                class="mb-3 aspect-square w-full overflow-hidden rounded bg-gray-100"
                            >
                                {#if product.images[0]}
                                    <img
                                        src={product.images[0].url}
                                        alt={product.name}
                                        class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                    />
                                {/if}
                            </div>
                            <div class="flex flex-col gap-1">
                                <p
                                    class="line-clamp-2 text-sm font-medium text-gray-900 group-hover:text-[#1a6bbf]"
                                >
                                    {product.name}
                                </p>
                                {#if product.manufacturer}
                                    <p class="text-xs text-muted-foreground">
                                        {product.manufacturer.name}
                                    </p>
                                {/if}
                                <p class="text-sm font-semibold text-[#1a3a5c]">
                                    {formatPrice(product.price)}*
                                </p>
                            </div>
                        </Link>
                    {/each}
                </div>

                {#snippet loading()}
                    <div class="mt-8 flex justify-center">
                        <div
                            class="size-6 animate-spin rounded-full border-2 border-[#1a6bbf] border-t-transparent"
                        ></div>
                    </div>
                {/snippet}
            </InfiniteScroll>
        {/if}
    </main>

    <AppFooter />
</div>
