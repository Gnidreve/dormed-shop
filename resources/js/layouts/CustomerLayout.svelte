<script lang="ts">
    import type { Snippet } from 'svelte';
    import ShopHeader from '@/components/ShopHeader.svelte';
    import { Toaster } from '@/components/ui/sonner';

    let { children }: { children?: Snippet } = $props();
</script>

<ShopHeader />
<main class="min-h-screen bg-background">
    {@render children?.()}
</main>
<Toaster />
