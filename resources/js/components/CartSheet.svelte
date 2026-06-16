<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ChevronLeft from 'lucide-svelte/icons/chevron-left';
    import Minus from 'lucide-svelte/icons/minus';
    import Plus from 'lucide-svelte/icons/plus';
    import ShoppingCart from 'lucide-svelte/icons/shopping-cart';
    import Check from 'lucide-svelte/icons/check';
    import X from 'lucide-svelte/icons/x';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import {
        Sheet,
        SheetClose,
        SheetContent,
        SheetTitle,
        SheetTrigger,
    } from '@/components/ui/sheet';

    let {
        cartTotal = 0,
        cartCount = 0,
    }: {
        cartTotal?: number;
        cartCount?: number;
    } = $props();

    function formatPrice(value: number | string): string {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: 'EUR',
        }).format(Number(value));
    }

    const formattedTotal = $derived(formatPrice(cartTotal));

    let couponCode = $state('');

    // Placeholder cart items — replace with real data later
    const items = [
        {
            id: 1,
            name: 'Akku Li-ion 4,2V (BT-410 LED)',
            sku: 'SW10010',
            deliveryFrom: '23.06.26',
            deliveryTo: '30.06.26',
            price: 46.41,
            quantity: 1,
            image: null,
        },
    ];

    let quantities = $state<Record<number, number>>(
        Object.fromEntries(items.map((i) => [i.id, i.quantity])),
    );

    const subtotal = $derived(
        items.reduce(
            (sum, item) => sum + item.price * (quantities[item.id] ?? 1),
            0,
        ),
    );
    const shipping = 9.52;
</script>

<Sheet>
    <SheetTrigger asChild>
        {#snippet children(props)}
            <Button variant="ghost" class="gap-2 px-3" onclick={props.onclick}>
                <div class="relative">
                    <ShoppingCart class="size-5" />
                    {#if cartCount > 0}
                        <span
                            class="absolute -right-2 -top-2 flex size-4 items-center justify-center rounded-full bg-[#1a6bbf] text-[10px] font-bold text-white"
                        >
                            {cartCount}
                        </span>
                    {/if}
                </div>
                <span class="text-sm font-medium text-[#1a3a5c]">
                    {formattedTotal}*
                </span>
            </Button>
        {/snippet}
    </SheetTrigger>

    <SheetContent
        side="right"
        hideClose
        class="flex w-full flex-col gap-0 p-0 sm:max-w-105"
    >
        <SheetTitle class="sr-only">Warenkorb</SheetTitle>

        <!-- Back button -->
        <SheetClose asChild>
            {#snippet children(props)}
                <button
                    onclick={props.onClick}
                    class="flex w-full items-center gap-2 border-b bg-gray-100 px-4 py-3 text-sm font-medium text-gray-600 hover:bg-gray-200 hover:text-gray-900"
                >
                    <ChevronLeft class="size-4" />
                    Weiter einkaufen
                </button>
            {/snippet}
        </SheetClose>

        <!-- Scrollable content -->
        <div class="flex-1 overflow-y-auto px-5 py-4">
            <!-- Header -->
            <div class="mb-5 flex items-baseline justify-between">
                <h2 class="text-xl font-bold text-gray-900">Warenkorb</h2>
                <span class="text-sm text-gray-500">
                    {items.length}
                    {items.length === 1 ? 'Position' : 'Positionen'}
                </span>
            </div>

            <!-- Items -->
            {#each items as item (item.id)}
                <div class="mb-4 rounded-lg border bg-white p-3">
                    <div class="flex gap-3">
                        <!-- Image -->
                        <div
                            class="flex size-16 shrink-0 items-center justify-center rounded border bg-gray-50"
                        >
                            {#if item.image}
                                <img
                                    src={item.image}
                                    alt={item.name}
                                    class="h-full w-full object-contain p-1"
                                />
                            {:else}
                                <ShoppingCart class="size-6 text-gray-300" />
                            {/if}
                        </div>

                        <!-- Info + remove -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p
                                    class="text-sm font-semibold leading-snug text-gray-900"
                                >
                                    {item.name}
                                </p>
                                <button
                                    class="shrink-0 rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                                    aria-label="Entfernen"
                                >
                                    <X class="size-4" />
                                </button>
                            </div>
                            <p class="mt-0.5 text-xs text-gray-400">
                                Produkt-Nr.: {item.sku}
                            </p>
                            <p class="text-xs text-gray-400">
                                Lieferung: {item.deliveryFrom} – {item.deliveryTo}
                            </p>
                        </div>
                    </div>

                    <!-- Quantity + price -->
                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex items-center rounded border">
                            <button
                                class="flex size-8 items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-800 disabled:opacity-40"
                                onclick={() =>
                                    (quantities[item.id] = Math.max(
                                        1,
                                        (quantities[item.id] ?? 1) - 1,
                                    ))}
                                disabled={quantities[item.id] <= 1}
                            >
                                <Minus class="size-3.5" />
                            </button>
                            <span class="w-8 text-center text-sm font-medium">
                                {quantities[item.id] ?? 1}
                            </span>
                            <button
                                class="flex size-8 items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-800"
                                onclick={() =>
                                    (quantities[item.id] =
                                        (quantities[item.id] ?? 1) + 1)}
                            >
                                <Plus class="size-3.5" />
                            </button>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">
                            {formatPrice(
                                item.price * (quantities[item.id] ?? 1),
                            )}*
                        </span>
                    </div>
                </div>
            {/each}

            <!-- Summary -->
            <div class="mt-4 flex flex-col gap-2 border-t pt-4 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Zwischensumme</span>
                    <span class="font-semibold">{formatPrice(subtotal)}*</span>
                </div>
                <div class="flex justify-between">
                    <div>
                        <span class="text-gray-600">Versandkosten</span>
                        <p class="text-xs text-gray-400">
                            Standardversand (DPD)
                        </p>
                    </div>
                    <span class="font-semibold">+ {formatPrice(shipping)}*</span
                    >
                </div>
            </div>

            <p class="mt-3 text-xs text-gray-400">
                * Preise inkl. MwSt. zzgl. Versandkosten
            </p>

            <!-- Coupon -->
            <div class="mt-5">
                <p class="mb-1.5 text-sm font-medium text-gray-700">
                    Gutscheincode
                </p>
                <div class="flex overflow-hidden rounded-md border">
                    <Input
                        disabled
                        bind:value={couponCode}
                        placeholder="Gutscheincode eingeben ..."
                        class="rounded-none border-0 focus-visible:ring-0"
                    />
                    <button
                        class="flex items-center justify-center border-l px-3 text-gray-500 hover:bg-gray-50 hover:text-gray-800"
                        aria-label="Gutschein einlösen"
                    >
                        <Check class="size-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="border-t bg-white px-5 pb-6 pt-4">
            <SheetClose asChild>
                {#snippet children(closeProps)}
                    <Button asChild class="w-full bg-[#0d1f44] text-white hover:bg-[#0d1f44]/90">
                        {#snippet children(btnProps)}
                            <Link
                                href="/checkout"
                                class={btnProps.class}
                                onclick={closeProps.onClick as () => void}
                            >
                                Zur Kasse
                            </Link>
                        {/snippet}
                    </Button>
                {/snippet}
            </SheetClose>
            <div class="mt-3 text-center">
                <Link
                    href="/warenkorb"
                    class="text-sm font-semibold text-[#1a3a5c] hover:underline"
                >
                    Warenkorb anzeigen
                </Link>
            </div>
        </div>
    </SheetContent>
</Sheet>
