<script lang="ts">
    type Customer = {
        name: string;
        email: string;
    } | null;

    let { customer }: { customer: Customer } = $props();
</script>

{#if customer}
    <div class="flex flex-col gap-0.5 leading-tight">
        <span class="font-medium text-foreground">{customer.name}</span>
        <a
            href="mailto:{customer.email}"
            class="text-sm text-muted-foreground hover:text-[#1a6bbf] hover:underline"
        >
            {customer.email}
        </a>
    </div>
{:else}
    <span class="text-sm text-muted-foreground">Kunde nicht verfügbar</span>
{/if}
