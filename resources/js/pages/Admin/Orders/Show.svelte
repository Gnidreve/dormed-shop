<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Bestellungen', href: '/admin/orders' },
            { title: 'Bestelldetail' },
        ],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import { Separator } from '@/components/ui/separator';
    import * as Table from '@/components/ui/table';
    import { formatPrice } from '@/lib/currency';

    type OrderItem = {
        id: number;
        product_name: string;
        unit_price: string;
        quantity: number;
    };

    type Payment = {
        id: number;
        paypal_order_id: string | null;
        status: string;
        amount: string;
        currency: string;
        payer_email: string | null;
        payer_name: string | null;
        created_at: string;
    };

    type AddressSnapshot = Record<string, string> | null;

    type OrderDetail = {
        id: number;
        status: string;
        total_amount: string;
        shipping_amount: string;
        shipping_address: AddressSnapshot;
        billing_address: AddressSnapshot;
        created_at: string;
        updated_at: string;
        customer: { id: number; name: string; email: string } | null;
        items: OrderItem[];
        payments: Payment[];
    };

    let { order }: { order: OrderDetail } = $props();

    const statusLabels: Record<string, string> = {
        pending: 'Ausstehend',
        processing: 'In Bearbeitung',
        paid: 'Bezahlt',
        completed: 'Abgeschlossen',
        cancelled: 'Storniert',
        failed: 'Fehlgeschlagen',
    };

    const paymentStatusLabels: Record<string, string> = {
        CREATED: 'Erstellt',
        APPROVED: 'Bestätigt',
        COMPLETED: 'Abgeschlossen',
        FAILED: 'Fehlgeschlagen',
        REFUNDED: 'Rückerstattet',
    };

    function formatAddress(a: AddressSnapshot): string {
        if (!a) return '—';
        const lines = [
            a.company,
            (a.salutation ? a.salutation + ' ' : '') + (a.first_name ?? '') + ' ' + (a.last_name ?? ''),
            (a.street ?? '') + ' ' + (a.house_number ?? ''),
            a.address_line2,
            (a.zip ?? '') + ' ' + (a.city ?? ''),
        ].filter(Boolean);
        return lines.join(', ') || '—';
    }
</script>

<AppHead title="Bestellung #{order.id} — Admin" />

<div class="flex flex-1 flex-col gap-6 p-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold">Bestellung #{order.id}</h1>
            <p class="text-sm text-muted-foreground">
                vom {new Date(order.created_at).toLocaleDateString('de-DE', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })}
            </p>
        </div>
        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium
            {order.status === 'paid' || order.status === 'completed' ? 'bg-green-100 text-green-700'
            : order.status === 'cancelled' || order.status === 'failed' ? 'bg-red-100 text-red-700'
            : order.status === 'processing' ? 'bg-blue-100 text-blue-700'
            : 'bg-yellow-100 text-yellow-700'}">
            {statusLabels[order.status] ?? order.status}
        </span>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <!-- Kundendaten -->
        <Card>
            <CardHeader>
                <CardTitle>Kunde</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-sm">
                {#if order.customer}
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Name</span>
                        <span class="font-medium">{order.customer.name}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">E-Mail</span>
                        <a href="mailto:{order.customer.email}" class="text-[#1a6bbf] hover:underline">{order.customer.email}</a>
                    </div>
                {:else}
                    <p class="text-muted-foreground">Kunde nicht verfügbar.</p>
                {/if}
            </CardContent>
        </Card>

        <!-- Zahlungsinformationen -->
        <Card>
            <CardHeader>
                <CardTitle>Zahlung</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Gesamtsumme</span>
                    <span class="font-semibold">{formatPrice(order.total_amount)}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Versandkosten</span>
                    <span>{formatPrice(order.shipping_amount)}</span>
                </div>
                {#if order.payments.length > 0}
                    <Separator />
                    <p class="font-medium text-muted-foreground">Transaktionen</p>
                    {#each order.payments as payment (payment.id)}
                        <div class="rounded bg-gray-50 p-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Status</span>
                                <span>{paymentStatusLabels[payment.status] ?? payment.status}</span>
                            </div>
                            {#if payment.payer_email}
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Zahler</span>
                                    <span>{payment.payer_name ?? payment.payer_email}</span>
                                </div>
                            {/if}
                            {#if payment.paypal_order_id}
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">PayPal-ID</span>
                                    <span class="font-mono text-xs">{payment.paypal_order_id}</span>
                                </div>
                            {/if}
                        </div>
                    {/each}
                {/if}
            </CardContent>
        </Card>
    </div>

    <!-- Adressen -->
    <div class="grid gap-6 md:grid-cols-2">
        <Card>
            <CardHeader>
                <CardTitle>Lieferadresse</CardTitle>
            </CardHeader>
            <CardContent class="whitespace-pre-wrap text-sm">
                {#if order.shipping_address}
                    <p class="text-muted-foreground">{formatAddress(order.shipping_address)}</p>
                {:else}
                    <p class="text-muted-foreground">—</p>
                {/if}
            </CardContent>
        </Card>
        <Card>
            <CardHeader>
                <CardTitle>Rechnungsadresse</CardTitle>
            </CardHeader>
            <CardContent class="whitespace-pre-wrap text-sm">
                {#if order.billing_address}
                    <p class="text-muted-foreground">{formatAddress(order.billing_address)}</p>
                {:else}
                    <p class="text-muted-foreground">—</p>
                {/if}
            </CardContent>
        </Card>
    </div>

    <!-- Positionen -->
    <Card>
        <CardHeader>
            <CardTitle>Positionen</CardTitle>
        </CardHeader>
        <CardContent>
            <Table.Root>
                <Table.Header>
                    <Table.Row>
                        <Table.Head>Produkt</Table.Head>
                        <Table.Head class="text-right">Einzelpreis</Table.Head>
                        <Table.Head class="text-center">Anzahl</Table.Head>
                        <Table.Head class="text-right">Summe</Table.Head>
                    </Table.Row>
                </Table.Header>
                <Table.Body>
                    {#each order.items as item (item.id)}
                        <Table.Row>
                            <Table.Cell class="font-medium">{item.product_name}</Table.Cell>
                            <Table.Cell class="text-right">{formatPrice(item.unit_price)}</Table.Cell>
                            <Table.Cell class="text-center">{item.quantity}</Table.Cell>
                            <Table.Cell class="text-right font-semibold">
                                {formatPrice((parseFloat(item.unit_price) * item.quantity).toFixed(2))}
                            </Table.Cell>
                        </Table.Row>
                    {:else}
                        <Table.Row>
                            <Table.Cell colspan="4" class="h-24 text-center text-muted-foreground">
                                Keine Positionen vorhanden.
                            </Table.Cell>
                        </Table.Row>
                    {/each}
                </Table.Body>
            </Table.Root>
        </CardContent>
    </Card>
</div>
