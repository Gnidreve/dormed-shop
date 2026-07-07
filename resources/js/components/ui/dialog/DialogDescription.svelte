<script lang="ts">
    import { cn } from '@/lib/utils';

    let { class: className = '' }: { class?: string } = $props();
</script>

<p class={cn('text-sm text-muted-foreground', className)}>
    <slot />
</p>
