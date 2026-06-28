<script module lang="ts">
    export const layout = {
        breadcrumbs: [{ title: 'Bestellungen', href: '/admin/orders' }],
    };
</script>

<script lang="ts">
    import {
        
        
        
        
        
        
        getCoreRowModel,
        getFilteredRowModel,
        getPaginationRowModel,
        getSortedRowModel
    } from '@tanstack/table-core';
import type {ColumnDef, ColumnFiltersState, PaginationState, RowSelectionState, SortingState, VisibilityState} from '@tanstack/table-core';
    import ChevronDown from 'lucide-svelte/icons/chevron-down';
    import AppHead from '@/components/AppHead.svelte';
    import TableCellLink from '@/components/TableCellLink.svelte';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import { FlexRender, createSvelteTable, renderComponent } from '@/components/ui/data-table';
    import {
        DropdownMenu,
        DropdownMenuCheckboxItem,
        DropdownMenuContent,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import { Input } from '@/components/ui/input';
    import * as Table from '@/components/ui/table';
    import { formatPrice } from '@/lib/currency';

    type Order = {
        id: number;
        status: string;
        total_amount: string;
        customer: { id: number; name: string; email: string } | null;
        created_at: string;
    };

    type Paginator = {
        data: Order[];
        total: number;
        current_page: number;
        last_page: number;
    };

    let { orders }: { orders: Paginator } = $props();

    const statusLabels: Record<string, string> = {
        pending: 'Ausstehend',
        processing: 'In Bearbeitung',
        completed: 'Abgeschlossen',
        cancelled: 'Storniert',
    };

    const columns: ColumnDef<Order>[] = [
        {
            id: 'select',
            header: ({ table }) =>
                renderComponent(Checkbox, {
                    checked: table.getIsAllPageRowsSelected(),
                    indeterminate: table.getIsSomePageRowsSelected() && !table.getIsAllPageRowsSelected(),
                    onCheckedChange: (v) => table.toggleAllPageRowsSelected(!!v),
                    'aria-label': 'Alle auswählen',
                }),
            cell: ({ row }) =>
                renderComponent(Checkbox, {
                    checked: row.getIsSelected(),
                    onCheckedChange: (v) => row.toggleSelected(!!v),
                    'aria-label': 'Zeile auswählen',
                }),
            enableSorting: false,
            enableHiding: false,
        },
        {
            accessorKey: 'id',
            header: '#',
            cell: ({ row }) =>
                renderComponent(TableCellLink, {
                    href: `/admin/orders/${row.original.id}`,
                    label: `#${row.original.id}`,
                }),
        },
        {
            id: 'customer',
            header: 'Kunde',
            accessorFn: (row) => row.customer?.name ?? '',
        },
        {
            accessorKey: 'status',
            header: 'Status',
            cell: ({ row }) => statusLabels[row.original.status] ?? row.original.status,
        },
        {
            accessorKey: 'total_amount',
            header: 'Gesamt',
            cell: ({ row }) => formatPrice(row.original.total_amount),
        },
        {
            accessorKey: 'created_at',
            header: 'Datum',
            cell: ({ row }) => new Date(row.original.created_at).toLocaleDateString('de-DE'),
        },
    ];

    let sorting = $state<SortingState>([]);
    let columnFilters = $state<ColumnFiltersState>([]);
    let rowSelection = $state<RowSelectionState>({});
    let pagination = $state<PaginationState>({ pageIndex: 0, pageSize: 15 });
    let columnVisibility = $state<VisibilityState>({});

    const table = createSvelteTable({
        get data() {
 return orders.data; 
},
        columns,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        onSortingChange: (u) => {
 sorting = typeof u === 'function' ? u(sorting) : u; 
},
        onColumnFiltersChange: (u) => {
 columnFilters = typeof u === 'function' ? u(columnFilters) : u; 
},
        onRowSelectionChange: (u) => {
 rowSelection = typeof u === 'function' ? u(rowSelection) : u; 
},
        onPaginationChange: (u) => {
 pagination = typeof u === 'function' ? u(pagination) : u; 
},
        onColumnVisibilityChange: (u) => {
 columnVisibility = typeof u === 'function' ? u(columnVisibility) : u; 
},
        state: {
            get sorting() {
 return sorting; 
},
            get columnFilters() {
 return columnFilters; 
},
            get rowSelection() {
 return rowSelection; 
},
            get pagination() {
 return pagination; 
},
            get columnVisibility() {
 return columnVisibility; 
},
        },
    });
</script>

<AppHead title="Bestellungen — Admin" />

<div class="flex h-full flex-1 flex-col gap-4 p-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">Bestellungen</h1>
        <span class="text-sm text-muted-foreground">{orders.total} gesamt</span>
    </div>

    <div class="flex items-center gap-2">
        <Input
            placeholder="Kunde filtern…"
            value={(table.getColumn('customer')?.getFilterValue() as string) ?? ''}
            oninput={(e) => table.getColumn('customer')?.setFilterValue(e.currentTarget.value)}
            class="max-w-sm"
        />

        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                {#snippet children(props)}
                    <Button {...props} variant="outline" class="ms-auto">
                        Spalten <ChevronDown class="ml-2 size-4" />
                    </Button>
                {/snippet}
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {#each table.getAllColumns().filter((c) => c.getCanHide()) as column (column.id)}
                    <DropdownMenuCheckboxItem
                        checked={column.getIsVisible()}
                        onCheckedChange={(v) => column.toggleVisibility(v)}
                    >
                        {typeof column.columnDef.header === 'string' ? column.columnDef.header : column.id}
                    </DropdownMenuCheckboxItem>
                {/each}
            </DropdownMenuContent>
        </DropdownMenu>
    </div>

    <div class="rounded-md border">
        <Table.Root>
            <Table.Header>
                {#each table.getHeaderGroups() as headerGroup (headerGroup.id)}
                    <Table.Row>
                        {#each headerGroup.headers as header (header.id)}
                            <Table.Head class="has-[[role=checkbox]]:ps-3">
                                {#if !header.isPlaceholder}
                                    <FlexRender content={header.column.columnDef.header} context={header.getContext()} />
                                {/if}
                            </Table.Head>
                        {/each}
                    </Table.Row>
                {/each}
            </Table.Header>
            <Table.Body>
                {#each table.getRowModel().rows as row (row.id)}
                    <Table.Row data-state={row.getIsSelected() && 'selected'}>
                        {#each row.getVisibleCells() as cell (cell.id)}
                            <Table.Cell class="has-[[role=checkbox]]:ps-3">
                                <FlexRender content={cell.column.columnDef.cell} context={cell.getContext()} />
                            </Table.Cell>
                        {/each}
                    </Table.Row>
                {:else}
                    <Table.Row>
                        <Table.Cell colspan={columns.length} class="h-24 text-center text-muted-foreground">
                            Keine Bestellungen vorhanden.
                        </Table.Cell>
                    </Table.Row>
                {/each}
            </Table.Body>
        </Table.Root>
    </div>

    <div class="flex items-center justify-between gap-2">
        <span class="text-sm text-muted-foreground">
            {#if Object.keys(rowSelection).length > 0}
                {Object.keys(rowSelection).length} ausgewählt
            {/if}
        </span>
        <div class="flex items-center gap-2">
            <span class="text-sm text-muted-foreground">
                Seite {table.getState().pagination.pageIndex + 1} von {table.getPageCount()}
            </span>
            <Button variant="outline" size="sm" onclick={() => table.previousPage()} disabled={!table.getCanPreviousPage()}>Zurück</Button>
            <Button variant="outline" size="sm" onclick={() => table.nextPage()} disabled={!table.getCanNextPage()}>Weiter</Button>
        </div>
    </div>
-e 
</div>
