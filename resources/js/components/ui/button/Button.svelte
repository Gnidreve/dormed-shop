<script lang="ts">
    import type { Snippet } from 'svelte';
    import {
        buttonVariants,
        type ButtonSize,
        type ButtonVariant,
    } from './variants';

    type AsChildProps = {
        class?: string;
        onClick?: (event: MouseEvent) => void;
        [key: string]: any;
    };

    let {
        children,
        asChild = false,
        variant = 'default',
        size = 'default',
        class: className = '',
        type = 'button',
        ref = $bindable(null),
        ...rest
    }: {
        children?: Snippet<[AsChildProps]>;
        asChild?: boolean;
        variant?: ButtonVariant;
        size?: ButtonSize;
        class?: string;
        type?: 'button' | 'submit' | 'reset' | null;
        ref?: HTMLElement | null;
        [key: string]: unknown;
    } = $props();

    const classes = () => buttonVariants({ variant, size, class: className });
</script>

{#if asChild}
    {@render children?.({ class: classes(), ...rest })}
{:else}
    <button bind:this={ref} class={classes()} type={type} {...rest}>
        {@render children?.({})}
    </button>
{/if}
