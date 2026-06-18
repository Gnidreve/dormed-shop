<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AlertCircle from 'lucide-svelte/icons/circle-alert';
    import AppFooter from '@/components/AppFooter.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import ShopHeader from '@/components/ShopHeader.svelte';
    import { Button } from '@/components/ui/button';
</script>

<AppHead title="Fehler bei der Bestellung" />

<div class="min-h-screen bg-gray-50">
    <ShopHeader />

    <main class="mx-auto max-w-2xl px-4 py-16 text-center lg:px-8">
        <div class="mb-5 flex justify-center">
            <div class="flex size-16 items-center justify-center rounded-full bg-red-100">
                <AlertCircle class="size-8 text-red-600" />
            </div>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">
            Bei Ihrer Bestellung ist ein Fehler aufgetreten.
        </h1>
        <p class="mt-3 text-sm text-gray-500">
            Bitte versuchen Sie es erneut oder kontaktieren Sie uns unter
            <a href="tel:023011886000" class="text-[#1a6bbf] hover:underline">02301 - 188600</a>.
        </p>
        <div class="mt-8 flex justify-center gap-3">
            <Button asChild variant="outline">
                {#snippet children(props)}
                    <Link href="/checkout/confirm" class={props.class}>Zurück zur Bestellung</Link>
                {/snippet}
            </Button>
            <Button asChild class="bg-[#0d1f44] text-white hover:bg-[#0d1f44]/90">
                {#snippet children(props)}
                    <Link href="/" class={props.class}>Zur Startseite</Link>
                {/snippet}
            </Button>
        </div>
    </main>

    <AppFooter />
</div>
