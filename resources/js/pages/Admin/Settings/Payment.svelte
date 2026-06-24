<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Einstellungen', href: '/admin/settings' },
            { title: 'Zahlungsarten', href: '/admin/settings/payment' },
        ],
    };
</script>

<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import { Copy, Check, Info, Loader2 } from 'lucide-svelte';
    import { toast } from 'svelte-sonner';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import * as AdminSettingController from '@/actions/App/Http/Controllers/Admin/SettingController';

    let {
        settings,
        hasSensitive,
        webhookUrl,
    }: {
        settings: Record<string, string>;
        hasSensitive: Record<string, boolean>;
        webhookUrl: string;
    } = $props();

    const form = useForm({
        settings: {
            'stripe.publishable_key': settings['stripe.publishable_key'] ?? '',
            'stripe.secret_key': '',
            'stripe.webhook_secret': '',
        },
    });

    let copied = $state(false);
    let checkingStripe = $state(false);

    function submit(e: SubmitEvent) {
        e.preventDefault();
        form.put(AdminSettingController.update.url());
    }

    async function copyWebhookUrl() {
        try {
            await navigator.clipboard.writeText(webhookUrl);
        } catch {
            const el = document.createElement('textarea');
            el.value = webhookUrl;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
        }
        copied = true;
        setTimeout(() => (copied = false), 2000);
    }

    async function checkStripe() {
        checkingStripe = true;
        try {
            const token = decodeURIComponent(
                document.cookie
                    .split('; ')
                    .find((r) => r.startsWith('XSRF-TOKEN='))
                    ?.split('=')[1] ?? '',
            );
            const res = await fetch('/admin/settings/stripe/check', {
                headers: { 'X-XSRF-TOKEN': token, Accept: 'application/json' },
            });
            const data = await res.json();
            if (res.ok) {
                toast.success(data.message);
            } else {
                toast.error(data.message);
            }
        } catch {
            toast.error('Verbindungsfehler');
        } finally {
            checkingStripe = false;
        }
    }
</script>

<AppHead title="Zahlungsarten — Einstellungen — Admin" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 max-w-2xl">
    <h1 class="text-xl font-semibold">Zahlungsarten</h1>

    <form onsubmit={submit} class="flex flex-col gap-4">
        <div class="rounded-lg border bg-card p-5 flex flex-col gap-4">
            <div class="flex flex-col gap-1.5">
                <Label for="stripe_publishable_key">Stripe Publishable Key</Label>
                <Input id="stripe_publishable_key" placeholder="pk_live_…" bind:value={form.settings['stripe.publishable_key']} />
            </div>
            <div class="flex flex-col gap-1.5">
                <Label for="stripe_key">
                    Stripe Secret Key
                    {#if hasSensitive['stripe.secret_key']}
                        <span class="text-xs text-muted-foreground ml-1">(gesetzt — leer lassen zum Beibehalten)</span>
                    {/if}
                </Label>
                <Input id="stripe_key" type="password" placeholder={hasSensitive['stripe.secret_key'] ? '••••••••' : 'sk_live_…'} bind:value={form.settings['stripe.secret_key']} />
            </div>
            <div class="flex flex-col gap-1.5">
                <Label>Webhook URL</Label>
                <div class="flex items-center gap-2">
                    <Input value={webhookUrl} readonly class="font-mono text-xs text-muted-foreground" />
                    <Button type="button" variant="outline" size="icon" onclick={copyWebhookUrl} aria-label="Kopieren">
                        {#if copied}
                            <Check class="size-4" />
                        {:else}
                            <Copy class="size-4" />
                        {/if}
                    </Button>
                </div>
                <p class="flex items-center gap-1 text-xs text-muted-foreground">
                    <Info class="size-3 shrink-0" />
                    Im Stripe Dashboard unter Developers → Webhooks eintragen.
                </p>
            </div>
            <div class="flex flex-col gap-1.5">
                <Label for="stripe_webhook">
                    Webhook Secret
                    {#if hasSensitive['stripe.webhook_secret']}
                        <span class="text-xs text-muted-foreground ml-1">(gesetzt — leer lassen zum Beibehalten)</span>
                    {/if}
                </Label>
                <Input id="stripe_webhook" type="password" placeholder={hasSensitive['stripe.webhook_secret'] ? '••••••••' : 'whsec_…'} bind:value={form.settings['stripe.webhook_secret']} />
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <Button type="button" variant="secondary" onclick={checkStripe} disabled={checkingStripe}>
                {#if checkingStripe}
                    <Loader2 class="size-4 animate-spin" />
                {/if}
                Verbindung prüfen
            </Button>
            <Button type="submit" disabled={form.processing}>
                {form.processing ? 'Speichern…' : 'Speichern'}
            </Button>
        </div>
    </form>
</div>