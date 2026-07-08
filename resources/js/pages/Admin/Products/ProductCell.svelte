<script lang="ts">
    type Manufacturer = { id: number; name: string } | null;

    let { name, manufacturer }: { name: string; manufacturer: Manufacturer } =
        $props();
</script>

<div class="flex flex-col gap-0.5 leading-tight">
    <span class="font-medium text-foreground">{name}</span>
    <span class="text-sm text-muted-foreground">
        {manufacturer?.name ?? '—'}
    </span>
</div>
