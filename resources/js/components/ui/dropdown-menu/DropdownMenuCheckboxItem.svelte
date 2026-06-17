<script lang="ts">
    import type { Snippet } from 'svelte';
    import Check from 'lucide-svelte/icons/check';
    import { cn } from '@/lib/utils';

    let {
        checked = false,
        onCheckedChange,
        class: className = '',
        children,
    }: {
        checked?: boolean;
        onCheckedChange?: (checked: boolean) => void;
        class?: string;
        children?: Snippet;
    } = $props();
</script>

<button
    type="button"
    role="menuitemcheckbox"
    aria-checked={checked}
    class={cn(
        'flex w-full cursor-pointer select-none items-center justify-between rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground',
        className,
    )}
    onclick={() => onCheckedChange?.(!checked)}
>
    {@render children?.()}
    <span class="flex size-4 items-center justify-center">
        {#if checked}
            <Check class="size-4" />
        {/if}
    </span>
</button>
