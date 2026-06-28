<script lang="ts">
    import type { Snippet } from 'svelte';

    let {
        title = '',
        description = null,
        ogImage = null,
        ogType = 'website',
        children,
    }: {
        title?: string;
        description?: string | null;
        ogImage?: string | null;
        ogType?: string;
        children?: Snippet;
    } = $props();

    const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
    const fullTitle = $derived(title ? `${title} - ${appName}` : appName);
    const metaDescription = $derived(description ?? null);
    const ogTitle = $derived(fullTitle);
</script>

<svelte:head>
    <title>{fullTitle}</title>

    {#if metaDescription}
        <meta name="description" content={metaDescription} />
    {/if}

    <!-- Open Graph -->
    <meta property="og:type" content={ogType} />
    <meta property="og:title" content={ogTitle} />
    {#if metaDescription}
        <meta property="og:description" content={metaDescription} />
    {/if}
    {#if ogImage}
        <meta property="og:image" content={ogImage} />
    {/if}

    <!-- Twitter Card -->
    <meta name="twitter:card" content={ogImage ? 'summary_large_image' : 'summary'} />
    <meta name="twitter:title" content={ogTitle} />
    {#if metaDescription}
        <meta name="twitter:description" content={metaDescription} />
    {/if}
    {#if ogImage}
        <meta name="twitter:image" content={ogImage} />
    {/if}

    {@render children?.()}
</svelte:head>
