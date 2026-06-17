<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import BookOpen from 'lucide-svelte/icons/book-open';
    import FolderGit2 from 'lucide-svelte/icons/folder-git-2';
    import LayoutGrid from 'lucide-svelte/icons/layout-grid';
    import Package from 'lucide-svelte/icons/package';
    import Tag from 'lucide-svelte/icons/tag';
    import Building2 from 'lucide-svelte/icons/building-2';
    import ShoppingCart from 'lucide-svelte/icons/shopping-cart';
    import Settings from 'lucide-svelte/icons/settings';
    import Users from 'lucide-svelte/icons/users';
    import type { Snippet } from 'svelte';
    import AppLogo from '@/components/AppLogo.svelte';
    import NavFooter from '@/components/NavFooter.svelte';
    import NavMain from '@/components/NavMain.svelte';
    import NavCustomer from '@/components/NavCustomer.svelte';
    import {
        Sidebar,
        SidebarContent,
        SidebarFooter,
        SidebarHeader,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';
    import { toUrl } from '@/lib/utils';
    import type { NavItem } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/admin',
            icon: LayoutGrid,
        },
        {
            title: 'Produkte',
            href: '/admin/products',
            icon: Package,
        },
        {
            title: 'Kategorien',
            href: '/admin/categories',
            icon: Tag,
        },
        {
            title: 'Hersteller',
            href: '/admin/manufacturers',
            icon: Building2,
        },
        {
            title: 'Kunden',
            href: '/admin/customers',
            icon: Users,
        },
        {
            title: 'Bestellungen',
            href: '/admin/orders',
            icon: ShoppingCart,
        },
        {
            title: 'Einstellungen',
            href: '/admin/settings',
            icon: Settings,
        },
    ];

    const footerNavItems: NavItem[] = [];
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
    </SidebarContent>

    <SidebarFooter>
        <NavFooter items={footerNavItems} />
        <NavCustomer />
    </SidebarFooter>
</Sidebar>
{@render children?.()}
