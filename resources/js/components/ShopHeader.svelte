<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Search from 'lucide-svelte/icons/search';
    import ShoppingCart from 'lucide-svelte/icons/shopping-cart';
    import User from 'lucide-svelte/icons/user';
    import X from 'lucide-svelte/icons/x';
    import ChevronRight from 'lucide-svelte/icons/chevron-right';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import * as ProductController from '@/actions/App/Http/Controllers/ProductController';

    let {
        cartTotal = 0,
        cartCount = 0,
    }: {
        cartTotal?: number;
        cartCount?: number;
    } = $props();

    const auth = $derived(page.props.auth);

    const formattedTotal = $derived(
        formatPrice(cartTotal),
    );

    type SearchResult = { id: number; name: string; price: string };

    let query = $state('');
    let results = $state<SearchResult[]>([]);
    let total = $state(0);
    let isOpen = $state(false);
    let isLoading = $state(false);
    let debounceTimer: ReturnType<typeof setTimeout>;
    let searchContainerEl: HTMLDivElement;

    function formatPrice(value: number | string): string {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: 'EUR',
        }).format(Number(value));
    }

    $effect(() => {
        const q = query;
        clearTimeout(debounceTimer);

        if (q.length < 2) {
            results = [];
            total = 0;
            isOpen = false;
            return;
        }

        isLoading = true;
        debounceTimer = setTimeout(async () => {
            const url = ProductController.search.url({ query: { q } });
            const res = await fetch(url, {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();
            results = data.results ?? [];
            total = data.total ?? 0;
            isOpen = true;
            isLoading = false;
        }, 300);

        return () => clearTimeout(debounceTimer);
    });

    function clearSearch() {
        query = '';
        results = [];
        isOpen = false;
    }

    function handleKeydown(e: KeyboardEvent) {
        if (e.key === 'Escape') {
            isOpen = false;
        }
    }

    function handleClickOutside(e: MouseEvent) {
        if (searchContainerEl && !searchContainerEl.contains(e.target as Node)) {
            isOpen = false;
        }
    }

    const allResultsUrl = $derived(
        ProductController.index.url({ query: { q: query } }),
    );
</script>

<svelte:window onclick={handleClickOutside} onkeydown={handleKeydown} />

<header class="border-b bg-white">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 lg:px-8">
        <!-- Logo -->
        <Link href="/" class="shrink-0">
            <img src="/logo.svg" alt="dormed 24" class="h-10 w-auto" />
        </Link>

        <!-- Search -->
        <div bind:this={searchContainerEl} class="relative flex-1">
            <div class="relative">
                <Input
                    type="search"
                    placeholder="Suchbegriff eingeben ..."
                    bind:value={query}
                    onfocus={() => query.length >= 2 && (isOpen = true)}
                    class="pr-10 [&::-webkit-search-cancel-button]:hidden"
                />
                {#if query}
                    <Button
                        variant="ghost"
                        size="icon"
                        onclick={clearSearch}
                        class="absolute right-0 top-0 h-full px-3 text-muted-foreground hover:text-foreground"
                    >
                        <X class="h-4 w-4" />
                    </Button>
                {:else}
                    <span
                        class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                    >
                        <Search class="h-4 w-4" />
                    </span>
                {/if}
            </div>

            <!-- Dropdown -->
            {#if isOpen && query.length >= 2}
                <div
                    class="absolute left-0 right-0 top-[calc(100%+4px)] z-50 overflow-hidden rounded-md border bg-white shadow-lg"
                >
                    {#if isLoading}
                        <div class="px-4 py-3 text-sm text-muted-foreground">
                            Suche ...
                        </div>
                    {:else if results.length === 0}
                        <div class="px-4 py-3 text-sm text-muted-foreground">
                            Keine Ergebnisse für „{query}"
                        </div>
                    {:else}
                        <ul>
                            {#each results as product (product.id)}
                                <li class="border-b last:border-b-0">
                                    <Link
                                        href="/products/{product.id}"
                                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-accent"
                                        onclick={() => (isOpen = false)}
                                    >
                                        <div
                                            class="h-9 w-9 shrink-0 rounded bg-gray-100"
                                        ></div>
                                        <span class="flex-1 truncate text-sm">
                                            {product.name}
                                        </span>
                                        <span
                                            class="shrink-0 text-sm font-medium text-[#1a3a5c]"
                                        >
                                            {formatPrice(product.price)}*
                                        </span>
                                    </Link>
                                </li>
                            {/each}
                        </ul>

                        <Link
                            href={allResultsUrl}
                            class="flex items-center justify-between border-t px-4 py-2.5 text-sm text-[#1a6bbf] hover:bg-accent"
                            onclick={() => (isOpen = false)}
                        >
                            <span class="flex items-center gap-1 font-medium">
                                <ChevronRight class="h-4 w-4" />
                                Alle Suchergebnisse anzeigen
                            </span>
                            <span class="text-muted-foreground">
                                {total}
                                {total === 1 ? 'Ergebnis' : 'Ergebnisse'}
                            </span>
                        </Link>
                    {/if}
                </div>
            {/if}
        </div>

        <!-- Actions -->
        <div class="flex shrink-0 items-center gap-1">
            <Button variant="ghost" size="icon" asChild>
                {#snippet children(props)}
                    <Link
                        href={auth?.user ? '/settings/profile' : '/login'}
                        {...props}
                    >
                        <User class="h-5 w-5" />
                        <span class="sr-only">Konto</span>
                    </Link>
                {/snippet}
            </Button>

            <Button variant="ghost" class="gap-2 px-3">
                <div class="relative">
                    <ShoppingCart class="h-5 w-5" />
                    {#if cartCount > 0}
                        <span
                            class="absolute -right-2 -top-2 flex h-4 w-4 items-center justify-center rounded-full bg-[#1a6bbf] text-[10px] font-bold text-white"
                        >
                            {cartCount}
                        </span>
                    {/if}
                </div>
                <span class="text-sm font-medium text-[#1a3a5c]">
                    {formattedTotal}*
                </span>
            </Button>
        </div>
    </div>
</header>
