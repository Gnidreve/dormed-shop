<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import Check from 'lucide-svelte/icons/check';
    import ChevronRight from 'lucide-svelte/icons/chevron-right';
    import Minus from 'lucide-svelte/icons/minus';
    import Plus from 'lucide-svelte/icons/plus';
    import X from 'lucide-svelte/icons/x';
    import AppHead from '@/components/AppHead.svelte';
    import ShopHeader from '@/components/ShopHeader.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import cartData from '@/data/cart.json';

    type CartItem = (typeof cartData.items)[number] & { quantity: number };

    let items = $state<CartItem[]>(cartData.items.map((i) => ({ ...i })));
    let couponCode = $state('');
    let productNumberInput = $state('');
    const selectedShipping = $derived(
        cartData.shippingMethods.find((m) => m.selected) ?? cartData.shippingMethods[0],
    );

    function formatPrice(value: number): string {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: 'EUR',
        }).format(value);
    }

    const subtotal = $derived(items.reduce((s, i) => s + i.unitPrice * i.quantity, 0));
    const shippingCost = $derived(selectedShipping?.price ?? 0);
    const total = $derived(subtotal + shippingCost);
    const vatAmount = $derived(total - total / (1 + cartData.vatRate / 100));
    const netTotal = $derived(total - vatAmount);

    function removeItem(id: number) {
        items = items.filter((i) => i.id !== id);
    }
</script>

<AppHead title="Warenkorb" />

<ShopHeader />

<main class="min-h-screen bg-gray-50">
    <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
        <h1 class="mb-6 text-3xl font-bold text-gray-900">Warenkorb</h1>

        {#if items.length === 0}
            <div class="rounded-lg border bg-white px-8 py-16 text-center text-gray-500">
                Ihr Warenkorb ist leer.
                <Link href="/" class="mt-3 block text-[#1a6bbf] hover:underline">
                    Weiter einkaufen
                </Link>
            </div>
        {:else}
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
                <!-- Left: Cart table -->
                <div class="flex-1">
                    <div class="overflow-hidden rounded-lg border bg-white">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-gray-50">
                                <tr>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-700">
                                        Produkt
                                    </th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700">
                                        Anzahl
                                    </th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-700">
                                        Stückpreis
                                    </th>
                                    <th class="px-5 py-3 text-right font-semibold text-gray-700">
                                        Summe
                                    </th>
                                    <th class="w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                {#each items as item (item.id)}
                                    <tr class="group">
                                        <!-- Product -->
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="flex size-14 shrink-0 items-center justify-center rounded border bg-gray-50"
                                                >
                                                    {#if item.imageUrl}
                                                        <img
                                                            src={item.imageUrl}
                                                            alt={item.name}
                                                            class="h-full w-full object-contain p-1"
                                                        />
                                                    {:else}
                                                        <div
                                                            class="size-8 rounded bg-gray-200"
                                                        ></div>
                                                    {/if}
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-900">
                                                        {item.name}
                                                    </p>
                                                    <p class="text-xs text-[#1a6bbf]">
                                                        Produkt-Nr.: {item.sku}
                                                    </p>
                                                    <p class="text-xs text-gray-400">
                                                        Lieferzeitraum: {item.deliveryFrom} – {item.deliveryTo}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <!-- Quantity -->
                                        <td class="px-4 py-4">
                                            <div
                                                class="mx-auto flex w-fit items-center rounded border"
                                            >
                                                <button
                                                    class="flex size-8 items-center justify-center text-gray-500 hover:bg-gray-50 disabled:opacity-40"
                                                    onclick={() =>
                                                        (item.quantity = Math.max(
                                                            1,
                                                            item.quantity - 1,
                                                        ))}
                                                    disabled={item.quantity <= 1}
                                                >
                                                    <Minus class="size-3.5" />
                                                </button>
                                                <span
                                                    class="w-10 text-center text-sm font-medium"
                                                >
                                                    {item.quantity}
                                                </span>
                                                <button
                                                    class="flex size-8 items-center justify-center text-gray-500 hover:bg-gray-50"
                                                    onclick={() => item.quantity++}
                                                >
                                                    <Plus class="size-3.5" />
                                                </button>
                                            </div>
                                        </td>
                                        <!-- Unit price -->
                                        <td
                                            class="px-4 py-4 text-right text-sm text-gray-700"
                                        >
                                            {formatPrice(item.unitPrice)}*
                                        </td>
                                        <!-- Line total -->
                                        <td
                                            class="px-5 py-4 text-right text-sm font-semibold text-gray-900"
                                        >
                                            {formatPrice(item.unitPrice * item.quantity)}*
                                        </td>
                                        <!-- Remove -->
                                        <td class="pr-3">
                                            <button
                                                onclick={() => removeItem(item.id)}
                                                class="rounded border p-1.5 text-gray-400 hover:border-gray-300 hover:text-gray-600"
                                                aria-label="Entfernen"
                                            >
                                                <X class="size-4" />
                                            </button>
                                        </td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>

                        <!-- Quick add + shipping info -->
                        <div
                            class="flex flex-wrap items-center gap-4 border-t px-5 py-3"
                        >
                            <div class="flex overflow-hidden rounded border">
                                <Input
                                    bind:value={productNumberInput}
                                    placeholder="Produktnummer eingeben"
                                    class="w-48 rounded-none border-0 focus-visible:ring-0"
                                />
                                <button
                                    class="flex items-center justify-center border-l px-3 text-gray-500 hover:bg-gray-50"
                                    aria-label="Hinzufügen"
                                >
                                    <Check class="size-4" />
                                </button>
                            </div>
                            <button
                                class="flex items-center gap-1 text-sm font-semibold text-[#1a3a5c] hover:underline"
                            >
                                Versanddetails öffnen
                                <ChevronRight class="size-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right: Summary -->
                <div class="w-full lg:w-80 xl:w-88">
                    <div class="rounded-lg border bg-white p-5">
                        <h2 class="mb-4 text-xl font-bold text-gray-900">
                            Zusammenfassung
                        </h2>

                        <div class="flex flex-col gap-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Zwischensumme</span>
                                <span class="font-medium">{formatPrice(subtotal)}*</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Versandkosten</span>
                                <span class="font-medium"
                                    >{formatPrice(shippingCost)}*</span
                                >
                            </div>
                            <div class="my-2 border-t pt-2">
                                <div class="flex justify-between">
                                    <span class="font-bold text-gray-900"
                                        >Gesamtsumme</span
                                    >
                                    <span class="font-bold text-gray-900"
                                        >{formatPrice(total)}*</span
                                    >
                                </div>
                            </div>
                            <div class="flex justify-between text-gray-500">
                                <span>Gesamtnettosumme</span>
                                <span>{formatPrice(netTotal)}</span>
                            </div>
                            <div class="flex justify-between text-gray-500">
                                <span>zzgl. {cartData.vatRate} % MwSt.</span>
                                <span>{formatPrice(vatAmount)}</span>
                            </div>
                        </div>

                        <div class="mt-5 border-t pt-4">
                            <p class="mb-1.5 text-sm font-medium text-gray-700">
                                Gutscheincode
                            </p>
                            <div class="flex overflow-hidden rounded border">
                                <Input
                                    bind:value={couponCode}
                                    placeholder="Gutscheincode eingeben ..."
                                    class="rounded-none border-0 focus-visible:ring-0"
                                />
                                <button
                                    class="flex items-center justify-center border-l px-3 text-gray-500 hover:bg-gray-50"
                                    aria-label="Einlösen"
                                >
                                    <Check class="size-4" />
                                </button>
                            </div>
                        </div>

                        <Button asChild class="mt-4 w-full bg-[#0d1f44] text-white hover:bg-[#0d1f44]/90">
                            {#snippet children(props)}
                                <Link href="/checkout/confirm" class={props.class}>
                                    Zur Kasse
                                </Link>
                            {/snippet}
                        </Button>
                    </div>

                    <p class="mt-3 text-center text-xs text-gray-400">
                        * Preise inkl. MwSt. zzgl. Versandkosten
                    </p>
                </div>
            </div>
        {/if}
    </div>
</main>
