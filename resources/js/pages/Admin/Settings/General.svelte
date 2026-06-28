<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            { title: 'Einstellungen', href: '/admin/settings' },
            { title: 'Allgemein', href: '/admin/settings/general' },
        ],
    };
</script>

<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import * as AdminSettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';

    let { settings }: { settings: Record<string, string> } = $props();

    const form = useForm({
        settings: {
            'shop.name': settings['shop.name'] ?? '',
            'shop.email': settings['shop.email'] ?? '',
            'shop.phone': settings['shop.phone'] ?? '',
        },
    });

    function submit(e: SubmitEvent) {
        e.preventDefault();
        form.put(AdminSettingController.update.url());
    }
</script>

<AppHead title="Allgemein — Einstellungen — Admin" />

<div class="flex h-full flex-1 flex-col gap-6 p-4 max-w-2xl">
    <h1 class="text-xl font-semibold">Allgemein</h1>

    <form onsubmit={submit} class="flex flex-col gap-4">
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

        <div class="flex justify-end gap-2">
            <Button type="submit" disabled={form.processing}>
                {form.processing ? 'Speichern…' : 'Speichern'}
            </Button>
        </div>
    </form>
</div>