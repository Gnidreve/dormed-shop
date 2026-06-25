<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import * as Table from '@/components/ui/table';
    import { formatPrice } from '@/lib/currency';

    type Order = {
        id: number;
        status: string;
        total_amount: string;
        created_at: string;
    };

    const statusLabels: Record<string, string> = {
        pending: 'Ausstehend',
        processing: 'In Bearbeitung',
        completed: 'Abgeschlossen',
        cancelled: 'Storniert',
    };

    const statusClass: Record<string, string> = {
        completed: 'bg-green-100 text-green-700',
        cancelled: 'bg-red-100 text-red-700',
        processing: 'bg-blue-100 text-blue-700',
        pending: 'bg-yellow-100 text-yellow-700',
    };

    let { orders }: { orders: Order[] } = $props();
</script>

<AppHead title="Bestellungen" />

<div class="space-y-6">
    <Heading
        title="Meine Bestellungen"
        description="Übersicht Ihrer bisherigen Bestellungen"
    />

    {#if orders.length === 0}
        <p class="py-8 text-center text-sm text-muted-foreground">
            Noch keine Bestellungen vorhanden.
        </p>
    {:else}
        <Table.Root>
            <Table.Header>
                <Table.Row>
                    <Table.Head>Nr.</Table.Head>
                    <Table.Head>Datum</Table.Head>
                    <Table.Head>Status</Table.Head>
                    <Table.Head class="text-right">Gesamt</Table.Head>
                </Table.Row>
            </Table.Header>
            <Table.Body>
                {#each orders as order (order.id)}
                    <Table.Row>
                        <Table.Cell class="font-medium">#{order.id}</Table.Cell>
                        <Table.Cell>
                            {new Date(order.created_at).toLocaleDateString('de-DE')}
                        </Table.Cell>
                        <Table.Cell>
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {statusClass[order.status] ?? 'bg-yellow-100 text-yellow-700'}"
                            >
                                {statusLabels[order.status] ?? order.status}
                            </span>
                        </Table.Cell>
                        <Table.Cell class="text-right font-semibold">
                            {formatPrice(order.total_amount)}
                        </Table.Cell>
                    </Table.Row>
                {/each}
            </Table.Body>
        </Table.Root>
    {/if}
</div>
