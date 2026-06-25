<script module lang="ts">
    export const layout = {
        breadcrumbs: [{ title: 'Produkte', href: '/admin/products' }, { title: 'Bearbeiten' }],
    };
</script>

<script lang="ts">
    import { useForm, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import * as Select from '@/components/ui/select';
    import * as AdminProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
    import { GripVertical, Trash2, ImagePlus, Star } from 'lucide-svelte';
    import { cn } from '@/lib/utils';

    type ProductImage = {
        id: number;
        path: string;
        sort_order: number;
        url: string;
    };

    type Product = {
        id: number;
        name: string;
        description: string | null;
        price: string;
        manufacturer_id: number | null;
        category_id: number | null;
        images: ProductImage[];
    };

    type Manufacturer = { id: number; name: string };
    type Category = { id: number; name: string };

    let {
        product,
        manufacturers,
        categories,
    }: { product: Product; manufacturers: Manufacturer[]; categories: Category[] } = $props();

    const form = useForm({
        name: product.name,
        description: product.description ?? '',
        price: product.price,
        manufacturer_id: product.manufacturer_id ? String(product.manufacturer_id) : '',
        category_id: product.category_id ? String(product.category_id) : '',
    });

    const selectedManufacturerLabel = $derived(
        manufacturers.find((m) => String(m.id) === form.manufacturer_id)?.name ?? 'Hersteller wählen',
    );

    const selectedCategoryLabel = $derived(
        categories.find((c) => String(c.id) === form.category_id)?.name ?? 'Kategorie wählen',
    );

    function submit(e: SubmitEvent) {
        e.preventDefault();
        form.put(AdminProductController.update.url(product.id));
    }

    // --- Image management ---

    let images = $state([...product.images]);
    let uploading = $state(false);
    let draggedId = $state<number | null>(null);
    let dragOverId = $state<number | null>(null);

    $effect(() => {
        images = [...product.images];
    });

    function uploadImage(e: Event) {
        const input = e.target as HTMLInputElement;
        const file = input.files?.[0];
        if (!file) return;

        uploading = true;
        const data = new FormData();
        data.append('image', file);

        router.post(`/admin/products/${product.id}/images`, data, {
            onFinish: () => {
                uploading = false;
                input.value = '';
            },
        });
    }

    function deleteImage(imageId: number) {
        router.delete(`/admin/products/${product.id}/images/${imageId}`);
    }

    function onDragStart(id: number) {
        draggedId = id;
    }

    function onDragOver(e: DragEvent, id: number) {
        e.preventDefault();
        dragOverId = id;
    }

    function onDrop(e: DragEvent, targetId: number) {
        e.preventDefault();
        if (draggedId === null || draggedId === targetId) return;

        const from = images.findIndex((img) => img.id === draggedId);
        const to = images.findIndex((img) => img.id === targetId);
        if (from === -1 || to === -1) return;

        const reordered = [...images];
        const [moved] = reordered.splice(from, 1);
        reordered.splice(to, 0, moved);
        images = reordered;

        router.patch(
            `/admin/products/${product.id}/images/reorder`,
            { ids: reordered.map((img) => img.id) },
        );

        draggedId = null;
        dragOverId = null;
    }

    function onDragEnd() {
        draggedId = null;
        dragOverId = null;
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
            <Label>Kategorie</Label>
            <Select.Root type="single" bind:value={form.category_id}>
                <Select.Trigger class="w-full">
                    {selectedCategoryLabel}
                </Select.Trigger>
                <Select.Content>
                    {#each categories as c (c.id)}
                        <Select.Item value={String(c.id)}>{c.name}</Select.Item>
                    {/each}
                </Select.Content>
            </Select.Root>
            {#if form.errors.category_id}
                <p class="text-sm text-destructive">{form.errors.category_id}</p>
            {/if}
        </div>

        <div class="flex flex-col gap-2">
            <Label>Hersteller</Label>
            <Select.Root type="single" bind:value={form.manufacturer_id}>
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

    <!-- Image Management -->
    <div class="flex max-w-2xl flex-col gap-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-semibold">Produktbilder</h2>
                <p class="text-sm text-muted-foreground">{images.length}/5 Bilder · Reihenfolge per Drag & Drop ändern</p>
            </div>
            <label
                class={cn(
                    'flex cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent',
                    (images.length >= 5 || uploading) && 'pointer-events-none opacity-50',
                )}
            >
                <ImagePlus class="size-4" />
                {uploading ? 'Lädt hoch…' : 'Bild hinzufügen'}
                <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="sr-only"
                    disabled={images.length >= 5 || uploading}
                    onchange={uploadImage}
                />
            </label>
        </div>

        {#if images.length === 0}
            <div class="flex h-32 items-center justify-center rounded-lg border border-dashed border-input text-sm text-muted-foreground">
                Noch keine Bilder hochgeladen
            </div>
        {:else}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                {#each images as image (image.id)}
                    <div
                        draggable="true"
                        ondragstart={() => onDragStart(image.id)}
                        ondragover={(e) => onDragOver(e, image.id)}
                        ondrop={(e) => onDrop(e, image.id)}
                        ondragend={onDragEnd}
                        class={cn(
                            'group relative overflow-hidden rounded-lg border border-input bg-muted transition-opacity',
                            draggedId === image.id && 'opacity-40',
                            dragOverId === image.id && draggedId !== image.id && 'ring-2 ring-primary',
                        )}
                    >
                        <img
                            src={image.url}
                            alt=""
                            class="aspect-square w-full object-cover"
                            draggable="false"
                        />

                        <!-- Drag handle -->
                        <div class="absolute left-1 top-1 cursor-grab rounded bg-black/50 p-0.5 text-white opacity-0 transition-opacity group-hover:opacity-100">
                            <GripVertical class="size-4" />
                        </div>

                        <!-- Hauptbild badge -->
                        {#if image.sort_order === 0}
                            <div class="absolute bottom-1 left-1 flex items-center gap-1 rounded bg-primary px-1.5 py-0.5 text-xs font-medium text-primary-foreground">
                                <Star class="size-3" />
                                Hauptbild
                            </div>
                        {/if}

                        <!-- Delete -->
                        <button
                            type="button"
                            onclick={() => deleteImage(image.id)}
                            class="absolute right-1 top-1 rounded bg-black/50 p-1 text-white opacity-0 transition-opacity hover:bg-destructive group-hover:opacity-100"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </div>
                {/each}
            </div>
        {/if}
    </div>
</div>
