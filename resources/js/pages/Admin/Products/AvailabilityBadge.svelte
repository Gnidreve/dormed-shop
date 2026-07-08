<script lang="ts">
    let { isAvailable }: { isAvailable: boolean } = $props();
</script>

<span
    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {isAvailable
        ? 'bg-green-100 text-green-700'
        : 'bg-red-100 text-red-700'}"
>
    {isAvailable ? 'Verfügbar' : 'Nicht verfügbar'}
</span>
