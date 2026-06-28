<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Building2 from 'lucide-svelte/icons/building-2';
    import CreditCard from 'lucide-svelte/icons/credit-card';
    import Layers from 'lucide-svelte/icons/layers';
    import LayoutGrid from 'lucide-svelte/icons/layout-grid';
    import Mail from 'lucide-svelte/icons/mail';
    import Package from 'lucide-svelte/icons/package';
    import Settings from 'lucide-svelte/icons/settings';
    import ShoppingCart from 'lucide-svelte/icons/shopping-cart';
    import Tag from 'lucide-svelte/icons/tag';
    import Truck from 'lucide-svelte/icons/truck';
    import Users from 'lucide-svelte/icons/users';
    import type { Snippet } from 'svelte';
    import AppLogo from '@/components/AppLogo.svelte';
    import NavAdmin from '@/components/NavAdmin.svelte';
    import NavGroup from '@/components/NavGroup.svelte';
    import NavMain from '@/components/NavMain.svelte';
    import {
        Sidebar,
        SidebarContent,
        SidebarFooter,
        SidebarHeader,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';
    import type { NavItem } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const mainNavItems: NavItem[] = [
        { title: 'Dashboard', href: '/admin', icon: LayoutGrid },
        { title: 'Kunden', href: '/admin/customers', icon: Users },
        { title: 'Bestellungen', href: '/admin/orders', icon: ShoppingCart },
    ];

    const catalogItems: NavItem[] = [
        { title: 'Produkte', href: '/admin/products', icon: Package },
        { title: 'Kategorien', href: '/admin/categories', icon: Tag },
        { title: 'Hersteller', href: '/admin/manufacturers', icon: Building2 },
    ];

    const settingsItems: NavItem[] = [
        { title: 'Allgemein', href: '/admin/settings/general', icon: Settings },
        { title: 'Mailversand', href: '/admin/settings/mail', icon: Mail },
        { title: 'Zahlungsarten', href: '/admin/settings/payment', icon: CreditCard },
        { title: 'Versandarten', href: '/admin/settings/shipping', icon: Truck },
    ];
</script>

<Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton size="lg" asChild>
                    {#snippet children(props)}
                        <Link
                            {...props}
                            href="/admin"
                            class={props.class}
                        >
                            <AppLogo />
                        </Link>
                    {/snippet}
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
        <NavMain items={mainNavItems} />
        <NavGroup title="Katalog" icon={Layers} items={catalogItems} />
        <NavGroup title="Einstellungen" icon={Settings} items={settingsItems} />
        <NavMain title="Katalog" items={catalogItems} />
        <NavMain title="Einstellungen" items={settingsItems} />
    </SidebarContent>

    <SidebarFooter>
        <NavAdmin />
    </SidebarFooter>
</Sidebar>
{@render children?.()}
