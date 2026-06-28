<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import ChevronRight from 'lucide-svelte/icons/chevron-right';
    import LogOut from 'lucide-svelte/icons/log-out';
    import MapPin from 'lucide-svelte/icons/map-pin';
    import Menu from 'lucide-svelte/icons/menu';
    import Package from 'lucide-svelte/icons/package';
    import Search from 'lucide-svelte/icons/search';
    import Settings from 'lucide-svelte/icons/settings';
    import User from 'lucide-svelte/icons/user';
    import UserPlus from 'lucide-svelte/icons/user-plus';
    import X from 'lucide-svelte/icons/x';
    import * as ProductController from '@/actions/App/Http/Controllers/ProductController';
    import CartSheet from '@/components/CartSheet.svelte';
    import CustomerInfo from '@/components/CustomerInfo.svelte';
    import TestModeBanner from '@/components/TestModeBanner.svelte';
    import { Button } from '@/components/ui/button';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuGroup,
        DropdownMenuItem,
        DropdownMenuLabel,
        DropdownMenuSeparator,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import { Input } from '@/components/ui/input';
    import {Sheet, SheetContent, SheetTitle, SheetTrigger} from '@/components/ui/sheet';
    import { formatPrice } from '@/lib/currency';
    import { toUrl } from '@/lib/utils';
    import { logout } from '@/routes';
    import customerRoutes from '@/routes/customer';
    import { edit as editProfile } from '@/routes/profile';
    import type { Cart, Customer } from '@/types';

    type NavCategory = { id: number; name: string; slug: string };

    const navCategories = $derived(page.props.navCategories as NavCategory[]);

    const staticNavStart = [
        { label: 'Startseite', href: '/' },
        { label: 'Alle Produkte', href: '/products' },
    ];

    const staticNavEnd = [{ label: 'Kontakt', href: '/kontakt' }];

    const auth = $derived(page.props.auth);
    const cart = $derived(page.props.cart as Cart);

    type SearchResult = {
        id: number;
        name: string;
        price: string;
    };

    let query = $state('');
    let results = $state<SearchResult[]>([]);
    let total = $state(0);
    let isOpen = $state(false);
    let isLoading = $state(false);
    let debounceTimer: ReturnType<typeof setTimeout>;
    let searchContainerEl: HTMLDivElement;

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
        if (
            searchContainerEl &&
            !searchContainerEl.contains(e.target as Node)
        ) {
            isOpen = false;
        }
    }

    const allResultsUrl = $derived(
        ProductController.index.url({ query: { q: query } }),
    );


</script>

<svelte:window onclick={handleClickOutside} onkeydown={handleKeydown} />

<TestModeBanner />
<header class="bg-white shadow-sm">
    <!-- Top row: logo + search + icons -->
    <div class="border-b">
        <div
            class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 lg:px-8"
        >
            <!-- Logo -->
            <Link href="/" class="shrink-0">
                <img src="/logo.svg" alt="dormed 24" class="h-10 w-auto" />
            </Link>

            <!-- Search (hidden on mobile) -->
            <div bind:this={searchContainerEl} class="relative mx-8 hidden flex-1 md:block">
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
                            <X class="size-4" />
                        </Button>
                    {:else}
                        <span
                            class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                        >
                            <Search class="size-4" />
                        </span>
                    {/if}
                </div>

                <!-- Search dropdown -->
                {#if isOpen && query.length >= 2}
                    <div
                        class="absolute left-0 right-0 top-[calc(100%+4px)] z-50 overflow-hidden rounded-md border bg-white shadow-lg"
                    >
                        {#if isLoading}
                            <div
                                class="px-4 py-3 text-sm text-muted-foreground"
                            >
                                Suche ...
                            </div>
                        {:else if results.length === 0}
                            <div
                                class="px-4 py-3 text-sm text-muted-foreground"
                            >
                                Keine Ergebnisse für „{query}"
                            </div>
                        {:else}
                            <ul>
                                {#each results as product (product.id)}
                                    <li class="border-b last:border-b-0">
                                        <Link
                                            href={ProductController.show.url(
                                                product.id,
                                            )}
                                            class="flex items-center gap-3 px-4 py-2.5 hover:bg-accent"
                                            onclick={() => (isOpen = false)}
                                        >
                                            <div
                                                class="size-9 shrink-0 rounded bg-gray-100"
                                            ></div>
                                            <span
                                                class="flex-1 truncate text-sm"
                                            >
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
                                <span
                                    class="flex items-center gap-1 font-medium"
                                >
                                    <ChevronRight class="size-4" />
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
                <!-- Mobile: burger menu -->
                <Sheet>
                    <SheetTrigger asChild>
                        {#snippet children(props)}
                            <Button variant="ghost" size="icon" class="md:hidden" onclick={props.onclick}>
                                <Menu class="size-5" />
                                <span class="sr-only">Menü</span>
                            </Button>
                        {/snippet}
                    </SheetTrigger>
                    <SheetContent side="left" class="w-72">
                        <SheetTitle class="sr-only">Navigation</SheetTitle>
                        <nav class="mt-6 flex flex-col gap-1">
                            {#each staticNavStart as item (item.label)}
                                <Link
                                    href={item.href}
                                    class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-accent hover:text-[#1a3a5c]"
                                >
                                    {item.label}
                                </Link>
                            {/each}
                            {#each navCategories as cat (cat.id)}
                                <Link
                                    href={`/${cat.slug}`}
                                    class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-accent hover:text-[#1a3a5c]"
                                >
                                    {cat.name}
                                </Link>
                            {/each}
                            {#each staticNavEnd as item (item.label)}
                                <Link
                                    href={item.href}
                                    class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-accent hover:text-[#1a3a5c]"
                                >
                                    {item.label}
                                </Link>
                            {/each}
                        </nav>
                    </SheetContent>
                </Sheet>

                <CartSheet {cart} />

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        {#snippet children(props)}
                            <Button variant="ghost" size="icon" {...props}>
                                <User class="size-5" />
                                <span class="sr-only">Konto</span>
                            </Button>
                        {/snippet}
                    </DropdownMenuTrigger>
                    <DropdownMenuContent class="w-56" align="end">
                        {#if auth?.user}
                            <DropdownMenuLabel class="p-0 font-normal">
                                <div
                                    class="flex items-center gap-2 px-1 py-1.5 text-left text-sm"
                                >
                                    <CustomerInfo
                                        user={auth.user as Customer}
                                        showEmail={true}
                                    />
                                </div>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuGroup>
                                <DropdownMenuItem asChild>
                                    {#snippet children(props)}
                                        <Link
                                            class={props.class}
                                            href={customerRoutes.orders.url()}
                                            onclick={props.onClick}
                                        >
                                            <Package class="mr-2 size-4" />
                                            Bestellungen
                                        </Link>
                                    {/snippet}
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    {#snippet children(props)}
                                        <Link
                                            class={props.class}
                                            href={toUrl(editProfile())}
                                            prefetch
                                            onclick={props.onClick}
                                        >
                                            <MapPin class="mr-2 size-4" />
                                            Adressen
                                        </Link>
                                    {/snippet}
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    {#snippet children(props)}
                                        <Link
                                            class={props.class}
                                            href={toUrl(editProfile())}
                                            prefetch
                                            onclick={props.onClick}
                                        >
                                            <Settings class="mr-2 size-4" />
                                            Einstellungen
                                        </Link>
                                    {/snippet}
                                </DropdownMenuItem>
                            </DropdownMenuGroup>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem asChild>
                                {#snippet children(props)}
                                    <Link
                                        class={props.class}
                                        href={logout()}
                                        as="button"
                                        onclick={() => {
                                            props.onClick?.();
                                            router.flushAll();
                                        }}
                                    >
                                        <LogOut class="mr-2 size-4" />
                                        Abmelden
                                    </Link>
                                {/snippet}
                            </DropdownMenuItem>
                        {:else}
                            <DropdownMenuGroup>
                                <DropdownMenuItem asChild>
                                    {#snippet children(props)}
                                        <Link
                                            class={props.class}
                                            href="/login"
                                            onclick={props.onClick}
                                        >
                                            <User class="mr-2 size-4" />
                                            Anmelden
                                        </Link>
                                    {/snippet}
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    {#snippet children(props)}
                                        <Link
                                            class={props.class}
                                            href="/register"
                                            onclick={props.onClick}
                                        >
                                            <UserPlus class="mr-2 size-4" />
                                            Registrieren
                                        </Link>
                                    {/snippet}
                                </DropdownMenuItem>
                            </DropdownMenuGroup>
                        {/if}
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    </div>

    <!-- Nav row (hidden on mobile) -->
    <div class="hidden border-t md:block">
        <div class="mx-auto max-w-7xl px-4 lg:px-8">
            <nav class="flex items-center justify-center">
                {#each staticNavStart as item (item.label)}
                    <Link
                        href={item.href}
                        class="border-b-2 border-transparent px-4 py-3 text-sm font-medium text-gray-600 transition-colors hover:border-[#1a6bbf] hover:text-[#1a3a5c]"
                    >
                        {item.label}
                    </Link>
                {/each}
                {#each navCategories as cat (cat.id)}
                    <Link
                        href={`/${cat.slug}`}
                        class="border-b-2 border-transparent px-4 py-3 text-sm font-medium text-gray-600 transition-colors hover:border-[#1a6bbf] hover:text-[#1a3a5c]"
                    >
                        {cat.name}
                    </Link>
                {/each}
                {#each staticNavEnd as item (item.label)}
                    <Link
                        href={item.href}
                        class="border-b-2 border-transparent px-4 py-3 text-sm font-medium text-gray-600 transition-colors hover:border-[#1a6bbf] hover:text-[#1a3a5c]"
                    >
                        {item.label}
                    </Link>
                {/each}
            </nav>
        </div>
    </div>
</header>
