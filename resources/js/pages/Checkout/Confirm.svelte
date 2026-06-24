<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import ChevronLeft from 'lucide-svelte/icons/chevron-left';
    import AppHead from '@/components/AppHead.svelte';
    import AddressForm from '@/components/AddressForm.svelte';
    import PayPalButton from '@/components/PayPalButton.svelte';
    import ShopHeader from '@/components/ShopHeader.svelte';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Separator } from '@/components/ui/separator';
    import cartRoutes from '@/routes/cart';
    import checkout from '@/routes/checkout';
    import { login } from '@/routes';
    import { formatPrice } from '@/lib/currency';
    import type { AddressData, Cart, Customer } from '@/types';

    let { cart, paypal_client_id }: { cart: Cart; paypal_client_id?: string | null } = $props();

    let agreedToTerms = $state(false);
    let billingSameAsShipping = $state(cart.billing_address === null);

    // Local address state, initialized from cart
    let shippingAddress = $state<AddressData>({ ...cart.shipping_address });
    let billingAddress = $state<AddressData | null>(
        cart.billing_address ? { ...cart.billing_address } : null,
    );

    let addressErrors = $state<Record<string, string>>({});
    let isSavingAddress = $state(false);

    const auth = $derived(page.props.auth);
    const customer = $derived(auth.user as Customer | undefined);

    const selectedPayment = $derived(cart.selected_payment_method);
    const selectedProvider = $derived(selectedPayment?.provider ?? null);
    const isPayPal = $derived(selectedProvider === 'paypal');
    const isStripe = $derived(selectedProvider === 'stripe');

    const addressComplete = $derived(
        shippingAddress.first_name !== '' &&
        shippingAddress.last_name !== '' &&
        shippingAddress.street !== '' &&
        shippingAddress.house_number !== '' &&
        shippingAddress.zip !== '' &&
        shippingAddress.city !== '',
    );

    function updateShipping(shippingMethod: string) {
        router.patch(
            cartRoutes.shipping.update.url(),
            { shipping_method: shippingMethod },
            { preserveScroll: true, preserveState: true },
        );
    }

    function updatePayment(paymentMethod: string) {
        router.patch(
            checkout.payment.update.url(),
            { payment_method: paymentMethod },
            { preserveScroll: true, preserveState: true },
        );
    }

    function handleAddressUpdate(event: CustomEvent<{ prefix: string; key: string; value: string }>) {
        const { prefix, key, value } = event.detail;

        if (prefix === 'shipping') {
            shippingAddress = { ...shippingAddress, [key]: value };
        } else if (prefix === 'billing') {
            billingAddress = { ...(billingAddress ?? defaultBillingAddress()), [key]: value };
        }

        // Clear error for this field
        const errorKey = `${prefix}_address.${key}`;
        if (addressErrors[errorKey]) {
            const next = { ...addressErrors };
            delete next[errorKey];
            addressErrors = next;
        }
    }

    function defaultBillingAddress(): AddressData {
        return {
            company: '', salutation: '', first_name: '', last_name: '',
            street: '', house_number: '', address_line2: '',
            zip: '', city: '', country: 'DE', phone: '',
        };
    }

    function enableBillingAddress() {
        billingSameAsShipping = false;
        billingAddress = defaultBillingAddress();
    }

    function disableBillingAddress() {
        billingSameAsShipping = true;
        billingAddress = null;
    }

    async function saveAddress() {
        isSavingAddress = true;
        addressErrors = {};

        const payload: Record<string, unknown> = {};

        // Build shipping_address
        payload.shipping_address = shippingAddress;
        payload.billing_same_as_shipping = billingSameAsShipping;

        if (!billingSameAsShipping && billingAddress) {
            payload.billing_address = billingAddress;
        }

        try {
            const resp = await fetch(checkout.address.update.url(), {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload),
            });

            if (resp.status === 302) {
                // Inertia redirect — reload errors from flash
                router.reload({ only: ['errors'], preserveScroll: true });
            } else if (!resp.ok) {
                const body = await resp.json();
                if (body.errors) {
                    addressErrors = body.errors;
                }
            }
        } catch {
            addressErrors = { _form: 'Adresse konnte nicht gespeichert werden.' };
        } finally {
            isSavingAddress = false;
        }
    }

    function submitOrder() {
        router.post(
            checkout.submit.url(),
            { agreed_to_terms: agreedToTerms },
            { preserveScroll: true },
        );
    }
</script>

<AppHead title="Bestellung abschließen" />

<ShopHeader />

<main class="min-h-screen bg-gray-50">
    <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
        <Link
            href={checkout.index.url()}
            class="mb-6 inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800"
        >
            <ChevronLeft class="size-4" />
            Zurück zur Bearbeitung
        </Link>

        <h1 class="mb-8 text-3xl font-bold text-gray-900">
            Bestellung abschließen
        </h1>

        <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
            <div class="flex flex-1 flex-col gap-6">
                <!-- AGB -->
                <div class="rounded-lg border bg-white p-5">
                    <h2 class="mb-2 font-bold text-gray-900">
                        AGB und Widerrufsbelehrung
                    </h2>
                    <Separator class="mb-4" />
                    <p class="mb-3 text-sm text-gray-600">
                        Bitte beachten Sie die
                        <Link href="/widerrufsbelehrung" class="text-[#1a6bbf] hover:underline">
                            Widerrufsbelehrung
                        </Link>.
                    </p>
                    <label class="flex cursor-pointer items-start gap-3">
                        <Checkbox bind:checked={agreedToTerms} class="mt-0.5" />
                        <span class="text-sm text-gray-700">
                            Ich habe die
                            <Link href="/agb" class="text-[#1a6bbf] hover:underline">AGB</Link>
                            gelesen und bin mit ihnen einverstanden.
                        </span>
                    </label>
                </div>

                <!-- Kundendaten -->
                <div class="rounded-lg border bg-white p-5">
                    <h2 class="mb-2 font-bold text-gray-900">Kundendaten</h2>
                    <Separator class="mb-4" />
                    {#if customer}
                        <p class="text-sm text-gray-700">
                            Angemeldet als <span class="font-semibold">{customer.name}</span> ({customer.email}).
                        </p>
                    {:else}
                        <p class="text-sm text-gray-700">
                            Sie bestellen aktuell ohne gespeicherte Kundendaten.
                        </p>
                    {/if}
                </div>

                <!-- Lieferadresse -->
                <div class="rounded-lg border bg-white p-5">
                    <div class="mb-2 flex items-center justify-between">
                        <h2 class="font-bold text-gray-900">Lieferadresse</h2>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={isSavingAddress || !addressComplete}
                            onclick={saveAddress}
                        >
                            {isSavingAddress ? 'Speichere...' : 'Adresse speichern'}
                        </Button>
                    </div>
                    <Separator class="mb-4" />
                    <div onaddressupdate={handleAddressUpdate}>
                        <AddressForm
                            data={shippingAddress}
                            errors={addressErrors}
                            prefix="shipping"
                            legend=""
                        />
                    </div>
                    {#if addressErrors._form}
                        <p class="mt-2 text-sm text-red-500">{addressErrors._form}</p>
                    {/if}
                </div>

                <!-- Rechnungsadresse -->
                <div class="rounded-lg border bg-white p-5">
                    <h2 class="mb-2 font-bold text-gray-900">Rechnungsadresse</h2>
                    <Separator class="mb-4" />
                    {#if billingSameAsShipping}
                        <p class="text-sm text-gray-600">
                            Identisch mit Lieferadresse.
                        </p>
                        <button
                            onclick={enableBillingAddress}
                            class="mt-2 text-sm text-[#1a6bbf] hover:underline"
                        >
                            Abweichende Rechnungsadresse eingeben
                        </button>
                    {:else}
                        <div onaddressupdate={handleAddressUpdate}>
                            <AddressForm
                                data={billingAddress ?? defaultBillingAddress()}
                                errors={addressErrors}
                                prefix="billing"
                                legend=""
                            />
                        </div>
                        <button
                            onclick={disableBillingAddress}
                            class="mt-2 text-sm text-[#1a6bbf] hover:underline"
                        >
                            Wie Lieferadresse verwenden
                        </button>
                    {/if}
                </div>

                <!-- Versandart -->
                <div class="rounded-lg border bg-white p-5">
                    <h2 class="mb-2 font-bold text-gray-900">Versandart</h2>
                    <Separator class="mb-4" />
                    <div class="flex flex-col gap-3">
                        {#each cart.shipping_methods as method (method.id)}
                            <label class="flex cursor-pointer items-start gap-3">
                                <input
                                    type="radio"
                                    name="shipping"
                                    value={method.id}
                                    checked={method.selected}
                                    onchange={() => updateShipping(method.id)}
                                    class="mt-0.5 accent-[#0d1f44]"
                                />
                                <span class="text-sm">
                                    <span class="font-semibold text-gray-900">
                                        {method.label}
                                    </span>
                                    <span class="text-gray-500"> - {formatPrice(method.price ?? 0)}</span>
                                    {#if method.description}
                                        <br />
                                        <span class="text-gray-500">{method.description}</span>
                                    {/if}
                                </span>
                            </label>
                        {/each}
                    </div>
                </div>

                <!-- Zahlungsart -->
                <div class="rounded-lg border bg-white p-5">
                    <h2 class="mb-2 font-bold text-gray-900">Zahlungsart</h2>
                    <Separator class="mb-4" />
                    <div class="flex flex-col gap-3">
                        {#each cart.payment_methods as method (method.id)}
                            <label class="flex cursor-pointer items-start gap-3">
                                <input
                                    type="radio"
                                    name="payment"
                                    value={method.id}
                                    checked={method.selected}
                                    onchange={() => updatePayment(method.id)}
                                    class="mt-0.5 accent-[#0d1f44]"
                                />
                                <span class="text-sm">
                                    <span class="font-semibold text-gray-900">
                                        {method.label}
                                    </span>
                                    {#if method.description}
                                        <br />
                                        <span class="text-gray-500">{method.description}</span>
                                    {/if}
                                </span>
                            </label>
                        {/each}
                    </div>
                </div>

                <!-- Produkttabelle -->
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
                                    MwSt.-Anteil
                                </th>
                                <th class="px-5 py-3 text-right font-semibold text-gray-700">
                                    Summe
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            {#each cart.items as item (item.product_id)}
                                <tr>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex size-14 shrink-0 items-center justify-center rounded border bg-gray-50"
                                            >
                                                <div class="size-8 rounded bg-gray-200"></div>
                                            </div>
                                            <div>
                                                <Link
                                                    href={item.product_url}
                                                    class="font-semibold text-[#1a6bbf] hover:underline"
                                                >
                                                    {item.name}
                                                </Link>
                                                <p class="text-xs text-gray-400">
                                                    Produkt-Nr.: {item.product_number}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        {item.quantity}
                                    </td>
                                    <td class="px-4 py-4 text-right text-gray-600">
                                        {formatPrice(
                                            (Number(item.line_total) * cart.vat_rate) /
                                                (100 + cart.vat_rate),
                                        )}
                                    </td>
                                    <td class="px-5 py-4 text-right font-semibold">
                                        {formatPrice(item.line_total)}*
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="w-full lg:w-80 xl:w-88">
                <div class="rounded-lg border bg-white p-5">
                    <h2 class="mb-4 text-xl font-bold text-gray-900">
                        Zusammenfassung
                    </h2>

                    <div class="flex flex-col gap-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Zwischensumme</span>
                            <span class="font-medium">{formatPrice(cart.subtotal)}*</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Versandkosten</span>
                            <span class="font-medium">{formatPrice(cart.shipping_total)}*</span>
                        </div>
                        <div class="my-2 border-t pt-2">
                            <div class="flex justify-between">
                                <span class="font-bold text-gray-900">Gesamtsumme</span>
                                <span class="font-bold text-gray-900">{formatPrice(cart.total)}*</span>
                            </div>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <span>Gesamtnettosumme</span>
                            <span>{formatPrice(cart.net_total)}</span>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <span>zzgl. {cart.vat_rate} % MwSt.</span>
                            <span>{formatPrice(cart.vat_amount)}</span>
                        </div>
                    </div>

                    {#if customer}
                        {#if isPayPal && paypal_client_id}
                            <div class="mt-6">
                                <PayPalButton
                                    total={Number(cart.total)}
                                    clientId={paypal_client_id ?? ''}
                                    disabled={!agreedToTerms || !addressComplete}
                                />
                            </div>
                            <p class="mt-3 text-sm text-gray-500">
                                Sie werden zu PayPal weitergeleitet, um die Zahlung zu bestätigen.
                            </p>
                        {:else if isStripe}
                            <Button
                                class="mt-6 w-full bg-[#0d1f44] text-white hover:bg-[#0d1f44]/90 disabled:opacity-50"
                                disabled={!agreedToTerms || cart.is_empty || !addressComplete}
                                onclick={submitOrder}
                            >
                                Zahlungspflichtig bestellen
                            </Button>
                            <p class="mt-3 text-sm text-gray-500">
                                Sie werden zur sicheren Zahlung über Stripe weitergeleitet.
                            </p>
                        {:else}
                            <p class="mt-3 text-sm text-gray-500">
                                Kein aktiver Zahlungsanbieter ausgewählt.
                            </p>
                        {/if}
                    {:else}
                        <Button asChild class="mt-6 w-full bg-[#0d1f44] text-white hover:bg-[#0d1f44]/90">
                            {#snippet children(props)}
                                <Link
                                    href={login.url({ query: { redirect: checkout.confirm.url() } })}
                                    class={props.class}
                                >
                                    Zum Login
                                </Link>
                            {/snippet}
                        </Button>
                        <p class="mt-3 text-sm text-gray-500">
                            Für den Bestellabschluss ist aktuell ein Kundenkonto erforderlich.
                        </p>
                    {/if}
                </div>

                <p class="mt-3 text-center text-xs text-gray-400">
                    * Preise inkl. MwSt. zzgl. Versandkosten
                </p>
            </div>
        </div>
    </div>
</main>
