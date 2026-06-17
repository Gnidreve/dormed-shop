<script lang="ts">
    import Ellipsis from 'lucide-svelte/icons/ellipsis';
    import ExternalLink from 'lucide-svelte/icons/external-link';
    import Pencil from 'lucide-svelte/icons/pencil';
    import { Button } from '@/components/ui/button';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuItem,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import * as AdminProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
    import * as ProductController from '@/actions/App/Http/Controllers/ProductController';

    let { product }: { product: { id: number; name: string } } = $props();
</script>

<DropdownMenu>
    <DropdownMenuTrigger asChild>
        {#snippet children(props)}
            <Button
                {...props}
                variant="ghost"
                size="icon"
                class="size-8"
                aria-label="Aktionen"
            >
                <Ellipsis class="size-4" />
            </Button>
        {/snippet}
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end">
        <DropdownMenuItem asChild>
            {#snippet children(props)}
                <a
                    class={props.class}
                    href={ProductController.show.url(product.id)}
                    target="_blank"
                    rel="noopener noreferrer"
                    onclick={props.onClick}
                >
                    <ExternalLink class="mr-2 size-4" />
                    Anzeigen
                </a>
            {/snippet}
        </DropdownMenuItem>
        <DropdownMenuItem asChild>
            {#snippet children(props)}
                <a
                    class={props.class}
                    href={AdminProductController.edit.url(product.id)}
                    onclick={props.onClick}
                >
                    <Pencil class="mr-2 size-4" />
                    Bearbeiten
                </a>
            {/snippet}
        </DropdownMenuItem>
    </DropdownMenuContent>
</DropdownMenu>
