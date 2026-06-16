<script module lang="ts">
    export const layout = {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: '/admin',
            },
        ],
    };
</script>

<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import PlaceholderPattern from '@/components/PlaceholderPattern.svelte';
    import { Button } from '@/components/ui/button';
    import * as AdminLoginController from '@/actions/App/Http/Controllers/Admin/LoginController';

    const admin = $derived((page.props.auth as any).admin);

    function logout() {
        router.post(AdminLoginController.logout.url());
    }
</script>

<AppHead title="Admin Dashboard" />

<div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold">
                Willkommen, {admin?.name ?? 'Admin'}
            </h2>
            <p class="text-sm text-muted-foreground">{admin?.email}</p>
        </div>
        <Button variant="outline" size="sm" onclick={logout}>
            Abmelden
        </Button>
    </div>

    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div
            class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
        <div
            class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
        <div
            class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
    </div>
    <div
        class="relative min-h-screen flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
    >
        <PlaceholderPattern />
    </div>
</div>
