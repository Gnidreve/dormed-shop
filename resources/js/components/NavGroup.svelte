<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ChevronRight from 'lucide-svelte/icons/chevron-right';
    import { Collapsible } from '@/components/ui/collapsible';
    import {
        SidebarGroup,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { toUrl } from '@/lib/utils';
    import type { NavItem } from '@/types';

    let {
        title,
        icon: Icon,
        items = [],
    }: {
        title: string;
        icon?: any;
        items: NavItem[];
    } = $props();

    const url = currentUrlState();
    const isAnyChildActive = $derived(
        items.some((item) => url.isCurrentUrl(item.href, url.currentUrl)),
    );

    let open = $state(false);

    $effect(() => {
        if (isAnyChildActive) {
            open = true;
        }
    });
</script>

<SidebarGroup class="px-2 py-0">
    <SidebarMenu>
        <SidebarMenuItem>
            <Collapsible bind:open class="group/collapsible w-full">
                    <SidebarMenuButton
                        tooltip={title}
                        isActive={isAnyChildActive}
                        onclick={() => {
 open = !open; 
}}
                    >
                        {#if Icon}
                            <Icon class="size-4 shrink-0" />
                        {/if}
                        <span class="flex-1">{title}</span>
                        <ChevronRight class="ml-auto size-4 shrink-0 transition-transform duration-200 {open ? 'rotate-90' : ''}" />
                    </SidebarMenuButton>
                    {#if open}
                        <ul class="mt-0.5 ml-4 border-l border-sidebar-border pl-2 flex flex-col gap-0.5">
                            {#each items as item (toUrl(item.href))}
                                <li>
                                    <SidebarMenuButton
                                        asChild
                                        size="sm"
                                        isActive={url.isCurrentUrl(item.href, url.currentUrl)}
                                        tooltip={item.title}
                                    >
                                        {#snippet children(props)}
                                            <Link
                                                {...props}
                                                href={toUrl(item.href)}
                                                class={props.class}
                                            >
                                                <span>{item.title}</span>
                                            </Link>
                                        {/snippet}
                                    </SidebarMenuButton>
                                </li>
                            {/each}
                        </ul>
                    {/if}
            </Collapsible>
        </SidebarMenuItem>
    </SidebarMenu>
</SidebarGroup>
