<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ChevronLeft from 'lucide-svelte/icons/chevron-left';
    import X from 'lucide-svelte/icons/x';
    import AppHead from '@/components/AppHead.svelte';
    import ShopHeader from '@/components/ShopHeader.svelte';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Separator } from '@/components/ui/separator';
    import cartData from '@/data/cart.json';

    let agreedToTerms = $state(false);
    let selectedPayment = $state(
        cartData.paymentMethods.find((m) => m.selected)?.id ?? cartData.paymentMethods[0].id,
    );
    let selectedShipping = $state(
        cartData.shippingMethods.find((m) => m.selected)?.id ?? cartData.shippingMethods[0].id,
    );

    const shippingCost = $derived(
        cartData.shippingMethods.find((m) => m.id === selectedShipping)?.price ?? 0,
    );
    const subtotal = $derived(
        cartData.items.reduce((s, i) => s + i.unitPrice * i.quantity, 0),
    );
    const total = $derived(subtotal + shippingCost);
    const vatAmount = $derived(total - total / (1 + cartData.vatRate / 100));
    const netTotal = $derived(total - vatAmount);

    function formatPrice(value: number): string {
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: 'EUR',
        }).format(value);
    }
</script>

<AppHead title="Bestellung abschließen" />

<ShopHeader />

<main class="min-h-screen bg-gray-50">
    <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
        <!-- Back -->
        <Link
            href="/checkout"
            class="mb-6 inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800"
        >
            <ChevronLeft class="size-4" />
            Zurück zur Bearbeitung
        </Link>

        <h1 class="mb-8 text-3xl font-bold text-gray-900">
            Bestellung abschließen
        </h1>

        <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
            <!-- Left column -->
            <div class="flex-1 flex flex-col gap-6">

                <!-- AGB -->
                <div class="rounded-lg border bg-white p-5">
                    <h2 class="mb-2 font-bold text-gray-900">
                        AGB und Widerrufsbelehrung
                    </h2>
                    <Separator class="mb-4" />
                    <p class="mb-3 text-sm text-gray-600">
                        Bitte beachten Sie die
                        <a href="/widerrufsbelehrung" class="text-[#1a6bbf] hover:underline">
                            Widerrufsbelehrung
                        </a>.
                    </p>
                    <label class="flex cursor-pointer items-start gap-3">
                        <Checkbox bind:checked={agreedToTerms} class="mt-0.5" />
                        <span class="text-sm text-gray-700">
                            Ich habe die
                            <a href="/agb" class="text-[#1a6bbf] hover:underline">AGB</a>
                            gelesen und bin mit ihnen einverstanden.
                        </span>
                    </label>
                </div>

                <!-- Addresses -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-lg border bg-white p-5">
                        <h2 class="mb-2 font-bold text-gray-900">Lieferadresse</h2>
                        <Separator class="mb-4" />
                        <address class="mb-4 text-sm not-italic leading-relaxed text-[#1a6bbf]">
                            {cartData.customer.name}<br />
                            {cartData.customer.street}<br />
                            {cartData.customer.zip} {cartData.customer.city}<br />
                            {cartData.customer.country}
                        </address>
                        <Button variant="outline" size="sm">
                            Lieferadresse ändern
                        </Button>
                    </div>
                    <div class="rounded-lg border bg-white p-5">
                        <h2 class="mb-2 font-bold text-gray-900">
                            Rechnungsadresse
                        </h2>
                        <Separator class="mb-4" />
                        {#if cartData.customer.billingMatchesDelivery}
                            <p class="mb-4 text-sm text-[#1a6bbf]">
                                Entspricht der Lieferadresse
                            </p>
                        {/if}
                        <Button variant="outline" size="sm">
                            Rechnungsadresse ändern
                        </Button>
                    </div>
                </div>

                <!-- Payment + Shipping -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-lg border bg-white p-5">
                        <h2 class="mb-2 font-bold text-gray-900">Zahlungsart</h2>
                        <Separator class="mb-4" />
                        <div class="flex flex-col gap-3">
                            {#each cartData.paymentMethods as method (method.id)}
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input
                                        type="radio"
                                        name="payment"
                                        value={method.id}
                                        bind:group={selectedPayment}
                                        class="mt-0.5 accent-[#0d1f44]"
                                    />
                                    <span class="text-sm">
                                        <span class="font-semibold text-gray-900">
                                            {method.label}
                                        </span>
                                        <br />
                                        <span class="text-gray-500">{method.description}</span>
                                    </span>
                                </label>
                            {/each}
                        </div>
                    </div>
                    <div class="rounded-lg border bg-white p-5">
                        <h2 class="mb-2 font-bold text-gray-900">Versandart</h2>
                        <Separator class="mb-4" />
                        <div class="flex flex-col gap-3">
                            {#each cartData.shippingMethods as method (method.id)}
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input
                                        type="radio"
                                        name="shipping"
                                        value={method.id}
                                        bind:group={selectedShipping}
                                        class="mt-0.5 accent-[#0d1f44]"
                                    />
                                    <span class="text-sm">
                                        <span class="font-semibold text-gray-900">
                                            {method.label}
                                        </span>
                                        {#if method.price !== null && method.price > 0}
                                            <span class="text-gray-500">
                                                – {formatPrice(method.price)}
                                            </span>
                                        {:else if method.price === 0}
                                            <span class="text-gray-500"> – kostenlos</span>
                                        {/if}
                                    </span>
                                </label>
                            {/each}
                        </div>
                    </div>
                </div>

                <!-- Order items (read-only, no qty controls) -->
                <div class="overflow-hidden rounded-lg border bg-white">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-gray-50">
                            <tr>
                                <th
                                    class="px-5 py-3 text-left font-semibold text-gray-700"
                                >
                                    Produkt
                                </th>
                                <th
                                    class="px-4 py-3 text-center font-semibold text-gray-700"
                                >
                                    Anzahl
                                </th>
                                <th
                                    class="px-4 py-3 text-right font-semibold text-gray-700"
                                >
                                    inkl. MwSt.
                                </th>
                                <th
                                    class="px-5 py-3 text-right font-semibold text-gray-700"
                                >
                                    Summe
                                </th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            {#each cartData.items as item (item.id)}
                                <tr>
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
                                                <Link
                                                    href="/products/{item.slug}"
                                                    class="font-semibold text-[#1a6bbf] hover:underline"
                                                >
                                                    {item.name}
                                                </Link>
                                                <p class="text-xs text-gray-400">
                                                    Produkt-Nr.: {item.sku}
                                                </p>
                                                <p class="text-xs text-gray-400">
                                                    Lieferzeitraum: {item.deliveryFrom} – {item.deliveryTo}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        {item.quantity}
                                    </td>
                                    <td
                                        class="px-4 py-4 text-right text-gray-600"
                                    >
                                        {formatPrice(
                                            (item.unitPrice * item.quantity * cartData.vatRate) /
                                                (100 + cartData.vatRate),
                                        )}
                                    </td>
                                    <td
                                        class="px-5 py-4 text-right font-semibold"
                                    >
                                        {formatPrice(item.unitPrice * item.quantity)}*
                                    </td>
                                    <td class="pr-3">
                                        <button
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
                </div>
            </div>

            <!-- Right: Summary + Order button -->
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
                            <span class="font-medium">{formatPrice(shippingCost)}*</span>
                        </div>
                        <div class="my-2 border-t pt-2">
                            <div class="flex justify-between">
                                <span class="font-bold text-gray-900">Gesamtsumme</span>
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

                    <Button
                        class="mt-6 w-full bg-[#0d1f44] text-white hover:bg-[#0d1f44]/90 disabled:opacity-50"
                        disabled={!agreedToTerms}
                    >
                        Zahlungspflichtig bestellen
                    </Button>
                </div>

                <p class="mt-3 text-center text-xs text-gray-400">
                    * Preise inkl. MwSt. zzgl. Versandkosten
                </p>

                <!-- AGB (sticky repeat for the sidebar) -->
                <div class="mt-4 rounded-lg border bg-white p-5">
                    <h2 class="mb-2 font-bold text-gray-900">
                        AGB und Widerrufsbelehrung
                    </h2>
                    <Separator class="mb-4" />
                    <p class="mb-3 text-sm text-gray-600">
                        Bitte beachten Sie die
                        <a href="/widerrufsbelehrung" class="text-[#1a6bbf] hover:underline">
                            Widerrufsbelehrung
                        </a>.
                    </p>
                    <label class="flex cursor-pointer items-start gap-3">
                        <Checkbox bind:checked={agreedToTerms} class="mt-0.5" />
                        <span class="text-sm text-gray-700">
                            Ich habe die
                            <a href="/agb" class="text-[#1a6bbf] hover:underline">AGB</a>
                            gelesen und bin mit ihnen einverstanden.
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</main>
