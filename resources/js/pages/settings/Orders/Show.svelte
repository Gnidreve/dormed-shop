<script lang="ts">
    import Check from 'lucide-svelte/icons/check';
    import CreditCard from 'lucide-svelte/icons/credit-card';
    import Package from 'lucide-svelte/icons/package';
    import Truck from 'lucide-svelte/icons/truck';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import { formatPrice } from '@/lib/currency';

    type OrderItem = {
        id: number;
        product_name: string;
        unit_price: string;
        quantity: number;
        image_url: string | null;
    };

    type AddressSnapshot = Record<string, string> | null;

    type OrderSummary = {
        subtotal: string;
        shipping_total: string;
        vat_rate: number;
        vat_amount: string;
        total: string;
    };

    type OrderDetail = {
        id: number;
        status: string;
        payment_method: string | null;
        payment_method_label: string | null;
        created_at: string | null;
        updated_at: string | null;
        shipping_address: AddressSnapshot;
        billing_address: AddressSnapshot;
        items: OrderItem[];
        summary: OrderSummary;
        customer_email: string;
    };

    type ProgressStep = {
        label: string;
        dateLabel: string;
        state: 'complete' | 'current' | 'upcoming';
        icon: typeof Check;
    };

    let { order }: { order: OrderDetail } = $props();

    const statusLabels: Record<string, string> = {
        pending: 'Ausstehend',
        paid: 'Bezahlt',
        processing: 'In Bearbeitung',
        completed: 'Abgeschlossen',
        cancelled: 'Storniert',
        failed: 'Fehlgeschlagen',
        refunded: 'Erstattet',
    };

    const statusClasses: Record<string, string> = {
        completed: 'bg-green-100 text-green-700',
        refunded: 'bg-green-100 text-green-700',
        cancelled: 'bg-red-100 text-red-700',
        failed: 'bg-red-100 text-red-700',
        processing: 'bg-blue-100 text-blue-700',
        paid: 'bg-blue-100 text-blue-700',
        pending: 'bg-yellow-100 text-yellow-700',
    };

    const statusIndex: Record<string, number> = {
        pending: 0,
        paid: 1,
        processing: 2,
        completed: 3,
        refunded: 3,
        cancelled: 0,
        failed: 0,
    };

    const createdAt = $derived(order.created_at ? new Date(order.created_at) : null);
    const updatedAt = $derived(order.updated_at ? new Date(order.updated_at) : createdAt);
    const orderCode = $derived(`ORD-${String(order.id).padStart(5, '0')}`);
    const currentStepIndex = $derived(statusIndex[order.status] ?? 0);
    const isTerminalError = $derived(order.status === 'cancelled' || order.status === 'failed');

    function formatDate(value: Date | null, options?: Intl.DateTimeFormatOptions): string {
        if (!value) return '-';

        return value.toLocaleDateString('de-DE', options ?? { day: '2-digit', month: 'short' });
    }

    function addDays(value: Date | null, days: number): Date | null {
        if (!value) return null;

        const date = new Date(value);
        date.setDate(date.getDate() + days);

        return date;
    }

    function formatAddress(address: AddressSnapshot): string[] {
        if (!address) return ['-'];

        const fullName = [address.salutation, address.first_name, address.last_name]
            .filter(Boolean)
            .join(' ')
            .trim();

        return [
            address.company ?? '',
            fullName,
            [address.street, address.house_number].filter(Boolean).join(' ').trim(),
            address.address_line2 ?? '',
            [address.zip, address.city].filter(Boolean).join(' ').trim(),
            address.country ?? '',
        ].filter((line) => line !== '');
    }

    const steps = $derived.by<ProgressStep[]>(() => {
        const baseDate = createdAt;

        return [
            {
                label: 'Bestellt',
                dateLabel: formatDate(baseDate),
                state: currentStepIndex > 0 ? 'complete' : currentStepIndex === 0 ? 'current' : 'upcoming',
                icon: Check,
            },
            {
                label: 'Bezahlt',
                dateLabel: formatDate(currentStepIndex >= 1 ? addDays(baseDate, 0) : addDays(baseDate, 1)),
                state: currentStepIndex > 1 ? 'complete' : currentStepIndex === 1 ? 'current' : 'upcoming',
                icon: CreditCard,
            },
            {
                label: 'In Bearbeitung',
                dateLabel: formatDate(currentStepIndex >= 2 ? updatedAt : addDays(baseDate, 2)),
                state: currentStepIndex > 2 ? 'complete' : currentStepIndex === 2 ? 'current' : 'upcoming',
                icon: Package,
            },
            {
                label: 'Abgeschlossen',
                dateLabel: currentStepIndex >= 3 ? formatDate(updatedAt) : `Vsl. ${formatDate(addDays(baseDate, 4))}`,
                state: currentStepIndex === 3 ? 'current' : 'upcoming',
                icon: Truck,
            },
        ];
    });
</script>

<AppHead title={`Bestellung ${orderCode}`} />

<div class="space-y-8">
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <Heading
                title={`Bestellung ${orderCode}`}
                description={`Bestellt am ${createdAt ? createdAt.toLocaleDateString('de-DE', { day: 'numeric', month: 'long', year: 'numeric' }) : '-'} · Bestätigung an ${order.customer_email}`}
            />

            <span class={`inline-flex w-fit items-center rounded-full px-3 py-1 text-sm font-medium ${statusClasses[order.status] ?? 'bg-yellow-100 text-yellow-700'}`}>
                {statusLabels[order.status] ?? order.status}
            </span>
        </div>

        <div class="rounded-3xl border bg-white p-5 shadow-sm">
            <div class="grid gap-6 md:grid-cols-4">
                {#each steps as step, index (step.label)}
                    <div class="relative flex flex-col gap-3">
                        {#if index < steps.length - 1}
                            <div class="absolute left-7 top-5 hidden h-0.5 w-[calc(100%-1rem)] md:block {index < currentStepIndex ? 'bg-[#0d1f44]' : 'bg-gray-200'}"></div>
                        {/if}

                        <div class={`relative z-10 flex size-14 items-center justify-center rounded-full border text-sm ${
                            step.state === 'complete' || step.state === 'current'
                                ? 'border-[#0d1f44] bg-[#0d1f44] text-white'
                                : 'border-gray-200 bg-white text-gray-400'
                        }`}>
                            <step.icon class="size-5" />
                        </div>

                        <div>
                            <p class="text-sm font-semibold text-gray-900">{step.label}</p>
                            <p class="text-sm text-muted-foreground">{step.dateLabel}</p>
                        </div>
                    </div>
                {/each}
            </div>

            {#if isTerminalError}
                <p class="mt-5 text-sm text-red-600">
                    Diese Bestellung wurde nicht regulär abgeschlossen. Der aktuelle Status lautet
                    <span class="font-medium">{statusLabels[order.status] ?? order.status}</span>.
                </p>
            {/if}
        </div>
    </div>

    <div class="space-y-4">
        <h2 class="text-2xl font-semibold text-gray-950">Positionen ({order.items.length})</h2>

        <div class="overflow-hidden rounded-3xl border bg-white shadow-sm">
            {#each order.items as item (item.id)}
                <div class="flex flex-col gap-4 border-b px-5 py-5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gray-100">
                            {#if item.image_url}
                                <img src={item.image_url} alt={item.product_name} class="size-full object-cover object-center" />
                            {:else}
                                <div class="size-8 rounded-full bg-gray-200"></div>
                            {/if}
                        </div>

                        <div class="space-y-1">
                            <p class="font-semibold text-gray-950">{item.product_name}</p>
                            <p class="text-sm text-muted-foreground">Menge: {item.quantity}</p>
                            <p class="text-sm text-muted-foreground">Einzelpreis: {formatPrice(item.unit_price)}*</p>
                        </div>
                    </div>

                    <div class="text-left text-lg font-semibold text-gray-950 sm:text-right">
                        {formatPrice((Number(item.unit_price) * item.quantity).toFixed(2))}*
                    </div>
                </div>
            {/each}
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-3xl border bg-white p-5 shadow-sm">
            <h3 class="mb-3 text-lg font-semibold text-gray-950">Lieferadresse</h3>
            <div class="space-y-1 text-sm text-muted-foreground">
                {#each formatAddress(order.shipping_address) as line (line)}
                    <p>{line}</p>
                {/each}
            </div>
        </div>

        <div class="rounded-3xl border bg-white p-5 shadow-sm">
            <h3 class="mb-3 text-lg font-semibold text-gray-950">Rechnungsadresse</h3>
            <div class="space-y-1 text-sm text-muted-foreground">
                {#each formatAddress(order.billing_address ?? order.shipping_address) as line (line)}
                    <p>{line}</p>
                {/each}
            </div>
        </div>

        <div class="rounded-3xl border bg-white p-5 shadow-sm">
            <h3 class="mb-3 text-lg font-semibold text-gray-950">Zahlung</h3>
            <div class="space-y-1 text-sm text-muted-foreground">
                <p>{order.payment_method_label ?? '-'}</p>
                <p>Gesamtbetrag: {formatPrice(order.summary.total)}*</p>
            </div>
        </div>
    </div>

    <div class="rounded-3xl border bg-white p-6 shadow-sm">
        <h2 class="mb-6 text-2xl font-semibold text-gray-950">Bestellübersicht</h2>

        <div class="space-y-4 text-sm">
            <div class="flex items-center justify-between gap-4">
                <span class="text-muted-foreground">Zwischensumme</span>
                <span class="font-medium text-gray-950">{formatPrice(order.summary.subtotal)}*</span>
            </div>
            <div class="flex items-center justify-between gap-4">
                <span class="text-muted-foreground">Versand</span>
                <span class="font-medium text-gray-950">{formatPrice(order.summary.shipping_total)}*</span>
            </div>
            <div class="flex items-center justify-between gap-4">
                <span class="text-muted-foreground">MwSt. ({order.summary.vat_rate} %)</span>
                <span class="font-medium text-gray-950">{formatPrice(order.summary.vat_amount)}*</span>
            </div>
            <div class="border-t pt-4">
                <div class="flex items-center justify-between gap-4">
                    <span class="text-xl font-semibold text-gray-950">Gesamt</span>
                    <span class="text-xl font-semibold text-gray-950">{formatPrice(order.summary.total)}*</span>
                </div>
            </div>
        </div>
    </div>
</div>
