<script lang="ts">
    import AppFooter from '@/components/AppFooter.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ShopHeader from '@/components/ShopHeader.svelte';
    import { Link } from '@inertiajs/svelte';
    import { formatPrice } from '@/lib/currency';
    import * as ProductController from '@/actions/App/Http/Controllers/ProductController';

    type ProductImage = { id: number; url: string; sort_order: number };

    type Product = {
        id: number;
        name: string;
        price: string;
        description: string | null;
        manufacturer: { id: number; name: string } | null;
        images: ProductImage[];
    };

    type Paginator = {
        data: Product[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };

    let { products, query }: { products: Paginator; query: string } = $props();
</script>

<AppHead title={query ? `Suchergebnisse für „${query}"` : 'Produkte'} />

<div class="min-h-screen bg-gray-50">
    <ShopHeader />

    <main class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
        <div class="mb-6 flex items-baseline justify-between">
            <div>
                {#if query}
                    <h1 class="text-xl font-semibold text-gray-900">
                        Suchergebnisse für „{query}"
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {products.total}
                        {products.total === 1 ? 'Ergebnis' : 'Ergebnisse'}
                    </p>
                {:else}
                    <h1 class="text-xl font-semibold text-gray-900">
                        Alle Produkte
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {products.total} Produkte
                    </p>
                {/if}
            </div>
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
            <div
                class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
            >
                {#each products.data as product (product.id)}
                    <Link
                        href={ProductController.show.url(product.id)}
                        class="group rounded-lg border bg-white p-3 shadow-sm transition hover:shadow-md"
                    >
                        <div class="mb-3 aspect-square w-full overflow-hidden rounded bg-gray-100">
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

            <!-- Pagination -->
            {#if products.last_page > 1}
                <nav class="mt-8 flex justify-center gap-1">
                    {#each products.links as link (link.label)}
                        {#if link.url}
                            <Link
                                href={link.url}
                                class="rounded border px-3 py-1.5 text-sm {link.active
                                    ? 'border-[#1a6bbf] bg-[#1a6bbf] text-white'
                                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'}"
                            >
                                <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                                {@html link.label}
                            </Link>
                        {:else}
                            <span
                                class="rounded border border-gray-200 px-3 py-1.5 text-sm text-gray-400"
                            >
                                <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                                {@html link.label}
                            </span>
                        {/if}
                    {/each}
                </nav>
            {/if}
        {/if}
    </main>

    <AppFooter />
</div>
