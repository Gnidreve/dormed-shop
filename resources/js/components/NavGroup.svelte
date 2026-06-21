<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ChevronRight from 'lucide-svelte/icons/chevron-right';
    import {
        Collapsible,
        CollapsibleContent,
        CollapsibleTrigger,
    } from '@/components/ui/collapsible';
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

    let open = $state(items.some((item) => url.isCurrentUrl(item.href, url.currentUrl)));
</script>

<SidebarGroup class="px-2 py-0">
    <SidebarMenu>
        <SidebarMenuItem>
            <Collapsible bind:open class="group/collapsible w-full">
                <CollapsibleTrigger>
                    {#snippet children()}
                        <SidebarMenuButton
                            tooltip={title}
                            isActive={isAnyChildActive}
                            data-state={open ? 'open' : 'closed'}
                        >
                            {#if Icon}
                                <Icon class="size-4 shrink-0" />
                            {/if}
                            <span class="flex-1">{title}</span>
                            <ChevronRight class="ml-auto size-4 shrink-0 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                        </SidebarMenuButton>
                    {/snippet}
                </CollapsibleTrigger>
                <CollapsibleContent>
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
                                            {#if item.icon}
                                                <item.icon class="size-4 shrink-0" />
                                            {/if}
                                            <span>{item.title}</span>
                                        </Link>
                                    {/snippet}
                                </SidebarMenuButton>
                            </li>
                        {/each}
                    </ul>
                </CollapsibleContent>
            </Collapsible>
        </SidebarMenuItem>
    </SidebarMenu>
</SidebarGroup>
