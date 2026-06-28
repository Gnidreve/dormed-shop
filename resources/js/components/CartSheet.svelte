<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import ChevronLeft from 'lucide-svelte/icons/chevron-left';
    import Minus from 'lucide-svelte/icons/minus';
    import Plus from 'lucide-svelte/icons/plus';
    import ShoppingCart from 'lucide-svelte/icons/shopping-cart';
    import X from 'lucide-svelte/icons/x';
    import { Button } from '@/components/ui/button';
    import {
        Sheet,
        SheetClose,
        SheetContent,
        SheetTitle,
        SheetTrigger,
    } from '@/components/ui/sheet';
    import { formatPrice } from '@/lib/currency';
    import cartRoutes from '@/routes/cart';
    import type { Cart } from '@/types';

    let { cart }: { cart: Cart } = $props();

    const formattedTotal = $derived(formatPrice(cart.total));

    function updateQuantity(productId: number, quantity: number) {
        router.patch(
            cartRoutes.items.update.url(productId),
            { quantity },
            { preserveScroll: true, preserveState: true },
        );
    }

    function removeItem(productId: number) {
        router.delete(cartRoutes.items.destroy.url(productId), {
            preserveScroll: true,
            preserveState: true,
        });
    }
</script>

<Sheet>
    <SheetTrigger asChild>
        {#snippet children(props)}
            <Button variant="ghost" class="gap-2 px-3" onclick={props.onclick}>
                <span class="text-sm font-medium text-[#1a3a5c]">
                    {formattedTotal}*
                </span>
                <div class="relative">
                    <ShoppingCart class="size-5" />
                    {#if cart.count > 0}
                        <span
                            class="absolute -right-2 -top-2 flex size-4 items-center justify-center rounded-full bg-[#1a6bbf] text-[10px] font-bold text-white"
                        >
                            {cart.count}
                        </span>
                    {/if}
                </div>
            </Button>
        {/snippet}
    </SheetTrigger>

    <SheetContent
        side="right"
        hideClose
        class="flex w-full flex-col gap-0 p-0 sm:max-w-105"
    >
        <SheetTitle class="sr-only">Warenkorb</SheetTitle>

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

        <div class="flex-1 overflow-y-auto px-5 py-4">
            <div class="mb-5 flex items-baseline justify-between">
                <h2 class="text-xl font-bold text-gray-900">Warenkorb</h2>
                <span class="text-sm text-gray-500">{cart.count} Artikel</span>
            </div>

            {#if cart.is_empty}
                <div class="rounded-lg border bg-white px-6 py-10 text-center text-sm text-gray-500">
                    Ihr Warenkorb ist leer.
                </div>
            {:else}
                {#each cart.items as item (item.product_id)}
                    <div class="mb-4 rounded-lg border bg-white p-3">
                        <div class="flex gap-3">
                            <div
                                class="flex size-16 shrink-0 items-center justify-center rounded border bg-gray-50"
                            >
                                <ShoppingCart class="size-6 text-gray-300" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <Link
                                        href={item.product_url}
                                        class="text-sm font-semibold leading-snug text-gray-900 hover:text-[#1a6bbf]"
                                    >
                                        {item.name}
                                    </Link>
                                    <button
                                        class="shrink-0 rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                                        aria-label="Entfernen"
                                        onclick={() => removeItem(item.product_id)}
                                    >
                                        <X class="size-4" />
                                    </button>
                                </div>
                                <p class="mt-0.5 text-xs text-gray-400">
                                    Produkt-Nr.: {item.product_number}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between">
                            <div class="flex items-center rounded border">
                                <button
                                    class="flex size-8 items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-800 disabled:opacity-40"
                                    onclick={() => updateQuantity(item.product_id, item.quantity - 1)}
                                    disabled={item.quantity <= 1}
                                >
                                    <Minus class="size-3.5" />
                                </button>
                                <span class="w-8 text-center text-sm font-medium">
                                    {item.quantity}
                                </span>
                                <button
                                    class="flex size-8 items-center justify-center text-gray-500 hover:bg-gray-50 hover:text-gray-800"
                                    onclick={() => updateQuantity(item.product_id, item.quantity + 1)}
                                >
                                    <Plus class="size-3.5" />
                                </button>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">
                                {formatPrice(item.line_total)}*
                            </span>
                        </div>
                    </div>
                {/each}
            {/if}

            <div class="mt-4 flex flex-col gap-2 border-t pt-4 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Zwischensumme</span>
                    <span class="font-semibold">{formatPrice(cart.subtotal)}*</span>
                </div>
                <div class="flex justify-between">
                    <div>
                        <span class="text-gray-600">Versandkosten</span>
                        <p class="text-xs text-gray-400">{cart.selected_shipping_method?.label}</p>
                    </div>
                    <span class="font-semibold">+ {formatPrice(cart.shipping_total)}*</span>
                </div>
            </div>

            <p class="mt-3 text-xs text-gray-400">
                * Preise inkl. MwSt. zzgl. Versandkosten
            </p>
        </div>

        <div class="border-t bg-white px-5 pb-6 pt-4">
            <SheetClose asChild>
                {#snippet children(closeProps)}
                    <Button
                        asChild
                        class="w-full bg-[#0d1f44] text-white hover:bg-[#0d1f44]/90"
                        disabled={cart.is_empty}
                    >
                        {#snippet children(btnProps)}
                            <Link
                                href={cartRoutes.index.url()}
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
                    href={cartRoutes.index.url()}
                    class="text-sm font-semibold text-[#1a3a5c] hover:underline"
                >
                    Warenkorb anzeigen
                </Link>
            </div>
        </div>
    </SheetContent>
</Sheet>
