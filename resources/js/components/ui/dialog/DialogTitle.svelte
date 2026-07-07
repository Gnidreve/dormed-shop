<script lang="ts">
    import { cn } from '@/lib/utils';

    let { class: className = '' }: { class?: string } = $props();
</script>

<h2 class={cn('text-lg font-semibold leading-none tracking-tight', className)}>
    <slot />
</h2>
