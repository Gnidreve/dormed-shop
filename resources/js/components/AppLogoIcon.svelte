<script lang="ts">
    let { class: className = 'h-10 w-auto' }: { class?: string } = $props();
</script>

<img src="/logo.svg" alt="dormed 24" class={className} />
