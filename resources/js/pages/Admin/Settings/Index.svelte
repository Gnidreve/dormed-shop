<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Dashboard', href: '/admin' },
            { title: 'Einstellungen', href: '/admin/settings' },
        ],
    };
</script>

<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import * as AdminSettingController from '@/actions/App/Http/Controllers/Admin/SettingController';

    let { settings }: { settings: Record<string, string | null> } = $props();

    const form = useForm({
        settings: {
            smtp_host: settings.smtp_host ?? '',
            smtp_port: settings.smtp_port ?? '',
            smtp_user: settings.smtp_user ?? '',
            smtp_password: settings.smtp_password ?? '',
            shop_name: settings.shop_name ?? '',
            shop_email: settings.shop_email ?? '',
        },
    });

    function submit(e: SubmitEvent) {
        e.preventDefault();
        form.put(AdminSettingController.update.url());
    }
</script>

<AppHead title="Einstellungen — Admin" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 max-w-2xl">
    <h1 class="text-xl font-semibold">Einstellungen</h1>

    <form onsubmit={submit} class="flex flex-col gap-6">
        <div class="rounded-lg border bg-card p-5">
            <h2 class="mb-4 font-semibold">Shop</h2>
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <Label for="shop_name">Shop-Name</Label>
                    <Input id="shop_name" bind:value={$form.settings.shop_name} />
                </div>
                <div class="flex flex-col gap-1.5">
                    <Label for="shop_email">Kontakt-E-Mail</Label>
                    <Input id="shop_email" type="email" bind:value={$form.settings.shop_email} />
                </div>
            </div>
        </div>

        <div class="rounded-lg border bg-card p-5">
            <h2 class="mb-4 font-semibold">SMTP</h2>
            <div class="flex flex-col gap-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <Label for="smtp_host">Host</Label>
                        <Input id="smtp_host" bind:value={$form.settings.smtp_host} />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="smtp_port">Port</Label>
                        <Input id="smtp_port" bind:value={$form.settings.smtp_port} />
                    </div>
                </div>
                <div class="flex flex-col gap-1.5">
                    <Label for="smtp_user">Benutzername</Label>
                    <Input id="smtp_user" bind:value={$form.settings.smtp_user} />
                </div>
                <div class="flex flex-col gap-1.5">
                    <Label for="smtp_password">Passwort</Label>
                    <Input id="smtp_password" type="password" bind:value={$form.settings.smtp_password} />
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <Button type="submit" disabled={$form.processing}>
                {$form.processing ? 'Speichern…' : 'Speichern'}
            </Button>
        </div>
    </form>
</div>
