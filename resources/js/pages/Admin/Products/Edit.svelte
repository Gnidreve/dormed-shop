<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/admin' },
            { title: 'Produkte', href: '/admin/products' },
            { title: 'Bearbeiten' },
        ],
    };
</script>

<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import * as Select from '@/components/ui/select';
    import * as AdminProductController from '@/actions/App/Http/Controllers/Admin/ProductController';

    type Product = {
        id: number;
        name: string;
        description: string | null;
        price: string;
        manufacturer_id: number | null;
    };

    type Manufacturer = { id: number; name: string };

    let {
        product,
        manufacturers,
    }: { product: Product; manufacturers: Manufacturer[] } = $props();

    const form = useForm({
        name: product.name,
        description: product.description ?? '',
        price: product.price,
        manufacturer_id: product.manufacturer_id ? String(product.manufacturer_id) : '',
    });

    const selectedManufacturerLabel = $derived(
        manufacturers.find((m) => String(m.id) === form.manufacturer_id)?.name ?? 'Hersteller wählen',
    );

    function submit(e: SubmitEvent) {
        e.preventDefault();
        form.put(AdminProductController.update.url(product.id));
    }
</script>

<AppHead title="{product.name} bearbeiten — Admin" />

<div class="flex h-full flex-1 flex-col gap-6 p-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">Produkt bearbeiten</h1>
    </div>

    <form onsubmit={submit} class="flex max-w-2xl flex-col gap-6">
        <div class="flex flex-col gap-2">
            <Label for="name">Name</Label>
            <Input
                id="name"
                bind:value={form.name}
                placeholder="Produktname"
            />
            {#if form.errors.name}
                <p class="text-sm text-destructive">{form.errors.name}</p>
            {/if}
        </div>

        <div class="flex flex-col gap-2">
            <Label for="price">Preis (€)</Label>
            <Input
                id="price"
                type="number"
                step="0.01"
                min="0"
                bind:value={form.price}
                placeholder="0.00"
            />
            {#if form.errors.price}
                <p class="text-sm text-destructive">{form.errors.price}</p>
            {/if}
        </div>

        <div class="flex flex-col gap-2">
            <Label>Hersteller</Label>
            <Select.Root
                type="single"
                bind:value={form.manufacturer_id}
            >
                <Select.Trigger class="w-full">
                    {selectedManufacturerLabel}
                </Select.Trigger>
                <Select.Content>
                    {#each manufacturers as m (m.id)}
                        <Select.Item value={String(m.id)}>{m.name}</Select.Item>
                    {/each}
                </Select.Content>
            </Select.Root>
            {#if form.errors.manufacturer_id}
                <p class="text-sm text-destructive">{form.errors.manufacturer_id}</p>
            {/if}
        </div>

        <div class="flex flex-col gap-2">
            <Label for="description">Beschreibung</Label>
            <textarea
                id="description"
                bind:value={form.description}
                rows={6}
                placeholder="Produktbeschreibung…"
                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
            ></textarea>
            {#if form.errors.description}
                <p class="text-sm text-destructive">{form.errors.description}</p>
            {/if}
        </div>

        <div class="flex gap-3">
            <Button type="submit" disabled={form.processing}>
                {form.processing ? 'Speichert…' : 'Speichern'}
            </Button>
            <Button
                type="button"
                variant="outline"
                onclick={() => history.back()}
            >
                Abbrechen
            </Button>
        </div>
    </form>
</div>
