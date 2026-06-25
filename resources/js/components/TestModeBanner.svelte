<script lang="ts">
    import { page, Link } from '@inertiajs/svelte';
    import { FlaskConical, X } from 'lucide-svelte';
</script>

{#if page.props.sandbox}
<div class="flex items-center justify-center gap-3 bg-amber-400 px-4 py-1.5 text-sm font-medium text-amber-950">
    <FlaskConical class="size-4 shrink-0" />
    <span>Sandbox-Modus aktiv — keine echten Transaktionen</span>
    <Link
        href="/sandbox/exit"
        class="ml-2 flex items-center gap-1 rounded border border-amber-700/30 bg-amber-500/40 px-2 py-0.5 text-xs font-semibold transition-colors hover:bg-amber-500/70"
    >
        <X class="size-3" />
        Beenden
    </Link>
</div>
{/if}
