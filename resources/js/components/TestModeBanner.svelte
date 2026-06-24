<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import { FlaskConical } from 'lucide-svelte';
</script>

{#if page.props.sandbox}
<div class="bg-amber-400 text-amber-950 text-sm font-medium px-4 py-2 flex items-center justify-center gap-2">
    <FlaskConical class="size-4 shrink-0" />
    Testmodus aktiv — Zahlungen werden über die Sandbox abgewickelt, keine echten Transaktionen.
</div>
{/if}
