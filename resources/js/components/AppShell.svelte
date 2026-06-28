<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import TestModeBanner from '@/components/TestModeBanner.svelte';
    import { SidebarProvider } from '@/components/ui/sidebar';
    import type { AppVariant } from '@/types';

    let {
        variant = 'sidebar',
        class: className = '',
        children,
    }: {
        variant?: AppVariant;
        class?: string;
        children?: Snippet;
    } = $props();

    const isOpen = $derived(page.props.sidebarOpen);
</script>

{#if variant === 'header'}
    <div class="flex min-h-screen w-full flex-col {className}">
        {@render children?.()}
    </div>
{:else}
    <div class="flex min-h-svh flex-col">
        <TestModeBanner />
        <SidebarProvider defaultOpen={isOpen} class="flex-1 min-h-0">
            {@render children?.()}
        </SidebarProvider>
    </div>
{/if}
