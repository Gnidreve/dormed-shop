<script module lang="ts">
    export const layout = {
        breadcrumbs: [{ title: 'Einstellungen', href: '/admin/settings' }],
    };
</script>

<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import { Copy, Check, Info, Loader2 } from 'lucide-svelte';
    import { toast } from 'svelte-sonner';
    import * as AdminSettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import * as Tabs from '@/components/ui/tabs';

    let {
        settings,
        hasSensitive,
        webhookUrl,
    }: {
        settings: Record<string, string>;
        hasSensitive: Record<string, boolean>;
        webhookUrl: string;
    } = $props();

    // svelte-ignore state_referenced_locally
    const form = useForm({
        settings: {
            'shop.name': settings['shop.name'] ?? '',
            'shop.email': settings['shop.email'] ?? '',
            'shop.phone': settings['shop.phone'] ?? '',
            'mail.smtp_host': settings['mail.smtp_host'] ?? '',
            'mail.smtp_port': settings['mail.smtp_port'] ?? '',
            'mail.smtp_user': settings['mail.smtp_user'] ?? '',
            'mail.smtp_password': '',
            'stripe.publishable_key': settings['stripe.publishable_key'] ?? '',
            'stripe.secret_key': '',
            'stripe.webhook_secret': '',
        },
    });

    let copied = $state(false);
    let activeTab = $state('unternehmen');
    let checkingStripe = $state(false);
    let checkingMail = $state(false);

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

    async function checkMail() {
        checkingMail = true;

        try {
            const token = decodeURIComponent(
                document.cookie
                    .split('; ')
                    .find((r) => r.startsWith('XSRF-TOKEN='))
                    ?.split('=')[1] ?? '',
            );
            const res = await fetch('/admin/settings/mail/check', {
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
            checkingMail = false;
        }
    }
</script>

<AppHead title="Einstellungen — Admin" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 max-w-2xl">
    <h1 class="text-xl font-semibold">Einstellungen</h1>

    <Tabs.Root bind:value={activeTab}>
        <Tabs.List>
            <Tabs.Trigger value="unternehmen">Unternehmen</Tabs.Trigger>
            <Tabs.Trigger value="mail">Mail</Tabs.Trigger>
            <Tabs.Trigger value="billing">Billing</Tabs.Trigger>
        </Tabs.List>

        <form onsubmit={submit} class="mt-4">
            <Tabs.Content value="unternehmen">
                <div class="rounded-lg border bg-card p-5 flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <Label for="shop_name">Shop-Name</Label>
                        <Input id="shop_name" bind:value={form.settings['shop.name']} />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="shop_email">Kontakt-E-Mail</Label>
                        <Input id="shop_email" type="email" bind:value={form.settings['shop.email']} />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="shop_phone">Telefon</Label>
                        <Input id="shop_phone" type="tel" bind:value={form.settings['shop.phone']} />
                    </div>
                </div>
            </Tabs.Content>

            <Tabs.Content value="mail">
                <div class="rounded-lg border bg-card p-5 flex flex-col gap-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <Label for="smtp_host">SMTP Host</Label>
                            <Input id="smtp_host" bind:value={form.settings['mail.smtp_host']} />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="smtp_port">Port</Label>
                            <Input id="smtp_port" bind:value={form.settings['mail.smtp_port']} />
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="smtp_user">Benutzername</Label>
                        <Input id="smtp_user" bind:value={form.settings['mail.smtp_user']} />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="smtp_password">
                            Passwort
                            {#if hasSensitive['mail.smtp_password']}
                                <span class="text-xs text-muted-foreground ml-1">(gesetzt — leer lassen zum Beibehalten)</span>
                            {/if}
                        </Label>
                        <Input id="smtp_password" type="password" placeholder={hasSensitive['mail.smtp_password'] ? '••••••••' : ''} bind:value={form.settings['mail.smtp_password']} />
                    </div>
                </div>
            </Tabs.Content>

            <Tabs.Content value="billing">
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
            </Tabs.Content>

            <div class="flex justify-end gap-2 mt-4">
                {#if activeTab === 'mail'}
                    <Button type="button" variant="secondary" onclick={checkMail} disabled={checkingMail}>
                        {#if checkingMail}
                            <Loader2 class="size-4 animate-spin" />
                        {/if}
                        Verbindung prüfen
                    </Button>
                {/if}
                {#if activeTab === 'billing'}
                    <Button type="button" variant="secondary" onclick={checkStripe} disabled={checkingStripe}>
                        {#if checkingStripe}
                            <Loader2 class="size-4 animate-spin" />
                        {/if}
                        Verbindung prüfen
                    </Button>
                {/if}
                <Button type="submit" disabled={form.processing}>
                    {form.processing ? 'Speichern…' : 'Speichern'}
                </Button>
            </div>
        </form>
    </Tabs.Root>
</div>
