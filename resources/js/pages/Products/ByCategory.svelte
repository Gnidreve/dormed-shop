<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppFooter from '@/components/AppFooter.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ShopHeader from '@/components/ShopHeader.svelte';
    import { formatPrice } from '@/lib/currency';
    import * as ProductController from '@/actions/App/Http/Controllers/ProductController';

    type Product = {
        id: number;
        name: string;
        price: string;
        description: string | null;
        manufacturer: { id: number; name: string } | null;
    };

    type Paginator = {
        data: Product[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };

    type Category = {
        id: number;
        name: string;
        slug: string;
        description: string | null;
    };

    let { category, products }: { category: Category; products: Paginator } = $props();
</script>

<AppHead title={category.name} />

<div class="min-h-screen bg-gray-50">
    <ShopHeader />

    <main class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
        <div class="mb-6 flex items-baseline justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{category.name}</h1>
                {#if category.description}
                    <p class="mt-1 text-sm text-muted-foreground">{category.description}</p>
                {:else}
                    <p class="mt-1 text-sm text-muted-foreground">{products.total} Produkte</p>
                {/if}
            </div>
        </div>

        {#if products.data.length === 0}
            <div class="py-16 text-center">
                <p class="text-muted-foreground">Keine Produkte in dieser Kategorie.</p>
                <Link
                    href={ProductController.index.url()}
                    class="mt-4 inline-block text-sm text-[#1a6bbf] hover:underline"
                >
                    Alle Produkte anzeigen
                </Link>
            </div>
        {:else}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                {#each products.data as product (product.id)}
                    <Link
                        href={ProductController.show.url(product.id)}
                        class="group rounded-lg border bg-white p-3 shadow-sm transition hover:shadow-md"
                    >
                        <div class="mb-3 aspect-square w-full rounded bg-gray-100"></div>
                        <div class="flex flex-col gap-1">
                            <p class="line-clamp-2 text-sm font-medium text-gray-900 group-hover:text-[#1a6bbf]">
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
                                {@html link.label}
                            </Link>
                        {:else}
                            <span class="rounded border border-gray-200 px-3 py-1.5 text-sm text-gray-400">
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
