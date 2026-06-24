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
        stripeWebhookUrl,
    }: {
        settings: Record<string, string>;
        hasSensitive: Record<string, boolean>;
        stripeWebhookUrl: string;
    } = $props();

    const form = useForm({
        settings: {
            'payment.provider': settings['payment.provider'] ?? 'stripe',
            'stripe.publishable_key': settings['stripe.publishable_key'] ?? '',
            'stripe.secret_key': '',
            'stripe.webhook_secret': '',
            'paypal.mode': settings['paypal.mode'] ?? 'sandbox',
            'paypal.sandbox.client_id': settings['paypal.sandbox.client_id'] ?? '',
            'paypal.sandbox.client_secret': '',
            'paypal.sandbox.merchant_id': settings['paypal.sandbox.merchant_id'] ?? '',
            'paypal.live.client_id': settings['paypal.live.client_id'] ?? '',
            'paypal.live.client_secret': '',
            'paypal.live.app_id': settings['paypal.live.app_id'] ?? '',
            'paypal.live.merchant_id': settings['paypal.live.merchant_id'] ?? '',
            'paypal.webhook_id': '',
        },
    });

    let copiedStripe = $state(false);
    let checkingStripe = $state(false);
    let checkingPayPal = $state(false);

    function submit(e: SubmitEvent) {
        e.preventDefault();
        form.put(AdminSettingController.update.url());
    }

    async function copyStripeWebhookUrl() {
        try {
            await navigator.clipboard.writeText(stripeWebhookUrl);
        } catch {
            const el = document.createElement('textarea');
            el.value = stripeWebhookUrl;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
        }
        copiedStripe = true;
        setTimeout(() => (copiedStripe = false), 2000);
    }

    async function fetchCheck(url: string, label: string, setLoading: (v: boolean) => void) {
        setLoading(true);
        try {
            const token = decodeURIComponent(
                document.cookie
                    .split('; ')
                    .find((r) => r.startsWith('XSRF-TOKEN='))
                    ?.split('=')[1] ?? '',
            );
            const res = await fetch(url, {
                headers: { 'X-XSRF-TOKEN': token, Accept: 'application/json' },
            });
            const data = await res.json();
            if (res.ok) {
                toast.success(data.message);
            } else {
                toast.error(data.message);
            }
        } catch {
            toast.error(`${label}: Verbindungsfehler`);
        } finally {
            setLoading(false);
        }
    }

    function checkStripe() {
        fetchCheck('/admin/settings/stripe/check', 'Stripe', (v) => (checkingStripe = v));
    }

    function checkPayPal() {
        fetchCheck('/admin/settings/paypal/check', 'PayPal', (v) => (checkingPayPal = v));
    }
</script>

<AppHead title="Zahlungsarten — Einstellungen — Admin" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 max-w-2xl">
    <h1 class="text-xl font-semibold">Zahlungsarten</h1>

    <form onsubmit={submit} class="flex flex-col gap-6">

        <!-- Provider-Auswahl -->
        <div class="rounded-lg border bg-card p-5 flex flex-col gap-3">
            <p class="text-sm font-medium">Aktiver Zahlungsanbieter</p>
            <p class="text-xs text-muted-foreground -mt-1">Nur einer der beiden Anbieter ist jeweils aktiv.</p>
            <div class="flex gap-3">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="radio"
                        name="payment_provider"
                        value="stripe"
                        class="accent-primary"
                        bind:group={form.settings['payment.provider']}
                    />
                    <span class="text-sm font-medium">Stripe</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        type="radio"
                        name="payment_provider"
                        value="paypal"
                        class="accent-primary"
                        bind:group={form.settings['payment.provider']}
                    />
                    <span class="text-sm font-medium">PayPal</span>
                </label>
            </div>
        </div>

        <!-- Stripe-Felder -->
        {#if form.settings['payment.provider'] === 'stripe'}
        <div class="rounded-lg border bg-card p-5 flex flex-col gap-4">
            <p class="text-sm font-semibold">Stripe</p>

            <div class="flex flex-col gap-1.5">
                <Label for="stripe_publishable_key">Publishable Key</Label>
                <Input id="stripe_publishable_key" placeholder="pk_live_…" bind:value={form.settings['stripe.publishable_key']} />
            </div>
            <div class="flex flex-col gap-1.5">
                <Label for="stripe_key">
                    Secret Key
                    {#if hasSensitive['stripe.secret_key']}
                        <span class="text-xs text-muted-foreground ml-1">(gesetzt — leer lassen zum Beibehalten)</span>
                    {/if}
                </Label>
                <Input id="stripe_key" type="password" placeholder={hasSensitive['stripe.secret_key'] ? '••••••••' : 'sk_live_…'} bind:value={form.settings['stripe.secret_key']} />
            </div>
            <div class="flex flex-col gap-1.5">
                <Label>Webhook URL</Label>
                <div class="flex items-center gap-2">
                    <Input value={stripeWebhookUrl} readonly class="font-mono text-xs text-muted-foreground" />
                    <Button type="button" variant="outline" size="icon" onclick={copyStripeWebhookUrl} aria-label="Kopieren">
                        {#if copiedStripe}
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
                {#if checkingStripe}<Loader2 class="size-4 animate-spin" />{/if}
                Verbindung prüfen
            </Button>
            <Button type="submit" disabled={form.processing}>
                {form.processing ? 'Speichern…' : 'Speichern'}
            </Button>
        </div>
        {/if}

        <!-- PayPal-Felder -->
        {#if form.settings['payment.provider'] === 'paypal'}
        <div class="rounded-lg border bg-card p-5 flex flex-col gap-4">
            <p class="text-sm font-semibold">PayPal</p>

            <!-- Modus -->
            <div class="flex flex-col gap-1.5">
                <Label>Modus</Label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                        <input type="radio" name="paypal_mode" value="sandbox" class="accent-primary" bind:group={form.settings['paypal.mode']} />
                        Sandbox (Test)
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                        <input type="radio" name="paypal_mode" value="live" class="accent-primary" bind:group={form.settings['paypal.mode']} />
                        Live
                    </label>
                </div>
            </div>

            <!-- Sandbox -->
            {#if form.settings['paypal.mode'] === 'sandbox'}
            <div class="flex flex-col gap-3 rounded-md bg-muted/40 p-4">
                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Sandbox-Zugangsdaten</p>
                <div class="flex flex-col gap-1.5">
                    <Label for="pp_sb_client_id">Client ID</Label>
                    <Input id="pp_sb_client_id" bind:value={form.settings['paypal.sandbox.client_id']} />
                </div>
                <div class="flex flex-col gap-1.5">
                    <Label for="pp_sb_client_secret">
                        Client Secret
                        {#if hasSensitive['paypal.sandbox.client_secret']}
                            <span class="text-xs text-muted-foreground ml-1">(gesetzt — leer lassen zum Beibehalten)</span>
                        {/if}
                    </Label>
                    <Input id="pp_sb_client_secret" type="password" placeholder={hasSensitive['paypal.sandbox.client_secret'] ? '••••••••' : ''} bind:value={form.settings['paypal.sandbox.client_secret']} />
                </div>
                <div class="flex flex-col gap-1.5">
                    <Label for="pp_sb_merchant_id">Merchant ID</Label>
                    <Input id="pp_sb_merchant_id" bind:value={form.settings['paypal.sandbox.merchant_id']} />
                </div>
            </div>
            {/if}

            <!-- Live -->
            {#if form.settings['paypal.mode'] === 'live'}
            <div class="flex flex-col gap-3 rounded-md bg-muted/40 p-4">
                <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Live-Zugangsdaten</p>
                <div class="flex flex-col gap-1.5">
                    <Label for="pp_live_client_id">Client ID</Label>
                    <Input id="pp_live_client_id" bind:value={form.settings['paypal.live.client_id']} />
                </div>
                <div class="flex flex-col gap-1.5">
                    <Label for="pp_live_client_secret">
                        Client Secret
                        {#if hasSensitive['paypal.live.client_secret']}
                            <span class="text-xs text-muted-foreground ml-1">(gesetzt — leer lassen zum Beibehalten)</span>
                        {/if}
                    </Label>
                    <Input id="pp_live_client_secret" type="password" placeholder={hasSensitive['paypal.live.client_secret'] ? '••••••••' : ''} bind:value={form.settings['paypal.live.client_secret']} />
                </div>
                <div class="flex flex-col gap-1.5">
                    <Label for="pp_live_app_id">App ID</Label>
                    <Input id="pp_live_app_id" placeholder="APP-…" bind:value={form.settings['paypal.live.app_id']} />
                </div>
                <div class="flex flex-col gap-1.5">
                    <Label for="pp_live_merchant_id">Merchant ID</Label>
                    <Input id="pp_live_merchant_id" bind:value={form.settings['paypal.live.merchant_id']} />
                </div>
            </div>
            {/if}

            <!-- Webhook ID -->
            <div class="flex flex-col gap-1.5">
                <Label for="pp_webhook_id">
                    Webhook ID
                    {#if hasSensitive['paypal.webhook_id']}
                        <span class="text-xs text-muted-foreground ml-1">(gesetzt — leer lassen zum Beibehalten)</span>
                    {/if}
                </Label>
                <Input id="pp_webhook_id" type="password" placeholder={hasSensitive['paypal.webhook_id'] ? '••••••••' : ''} bind:value={form.settings['paypal.webhook_id']} />
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <Button type="button" variant="secondary" onclick={checkPayPal} disabled={checkingPayPal}>
                {#if checkingPayPal}<Loader2 class="size-4 animate-spin" />{/if}
                Verbindung prüfen
            </Button>
            <Button type="submit" disabled={form.processing}>
                {form.processing ? 'Speichern…' : 'Speichern'}
            </Button>
        </div>
        {/if}

    </form>
</div>
