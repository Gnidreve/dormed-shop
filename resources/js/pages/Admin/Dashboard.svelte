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
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardHeader,
        CardTitle,
        CardDescription,
        CardContent,
    } from '@/components/ui/card';
    import { ChartContainer, type ChartConfig } from '@/components/ui/chart';
    import * as Popover from '@/components/ui/popover';
    import { Separator } from '@/components/ui/separator';
    import PlaceholderPattern from '@/components/PlaceholderPattern.svelte';
    import { scaleUtc } from 'd3-scale';
    import { Area, AreaChart, ChartClipPath } from 'layerchart';
    import { curveNatural } from 'd3-shape';
    import { cubicInOut } from 'svelte/easing';
    import * as AdminLoginController from '@/actions/App/Http/Controllers/Admin/LoginController';


    type ChartEntry = { date: string; orders: number; revenue: number };

    let { chartData }: { chartData: ChartEntry[] } = $props();

    const allData = $derived(chartData.map((d) => ({ ...d, date: new Date(d.date) })));

    const admin = $derived((page.props.auth as any).admin);

    function logout() {
        router.post(AdminLoginController.logout.url());
    }

    type TimeRange = '7d' | '30d' | '90d' | 'custom';

    let timeRange = $state<TimeRange>('7d');
    let customFrom = $state('');
    let customTo = $state('');
    let popoverOpen = $state(false);

    const presets: { value: Exclude<TimeRange, 'custom'>; label: string }[] = [
        { value: '7d', label: 'Letzte 7 Tage' },
        { value: '30d', label: 'Letzte 30 Tage' },
        { value: '90d', label: 'Letzte 3 Monate' },
    ];

    const fmt = (d: Date) =>
        d.toLocaleDateString('de-DE', { day: 'numeric', month: 'short' });

    const timeRangeLabel = $derived.by(() => {
        if (timeRange === 'custom' && customFrom && customTo) {
            const from = new Date(customFrom);
            const to = new Date(customTo);
            return `${fmt(from)} – ${fmt(to)}`;
        }
        return presets.find((p) => p.value === timeRange)?.label ?? 'Zeitraum';
    });

    const filteredData = $derived.by(() => {
        if (timeRange === 'custom') {
            const from = customFrom ? new Date(customFrom) : null;
            const to = customTo ? new Date(customTo + 'T23:59:59') : null;
            return allData.filter(
                (d) => (!from || d.date >= from) && (!to || d.date <= to),
            );
        }
        const days = timeRange === '7d' ? 7 : timeRange === '30d' ? 30 : 90;
        const cutoff = new Date();
        cutoff.setDate(cutoff.getDate() - days);
        return allData.filter((d) => d.date >= cutoff);
    });

    const dateRange = $derived.by(() => {
        if (!filteredData.length) return '';
        return `${fmt(filteredData[0].date)} – ${fmt(filteredData[filteredData.length - 1].date)}`;
    });

    function applyCustomRange() {
        if (customFrom && customTo) {
            timeRange = 'custom';
            popoverOpen = false;
        }
    }

    const ordersConfig = {
        orders: { label: 'Bestellungen', color: 'var(--chart-1)' },
    } satisfies ChartConfig;

    const revenueConfig = {
        revenue: { label: 'Umsatz', color: 'var(--chart-2)' },
    } satisfies ChartConfig;
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
    </div>

    <Card>
        <CardHeader
            class="flex items-center gap-2 space-y-0 border-b py-5 sm:flex-row"
        >
            <div class="grid flex-1 gap-1 text-center sm:text-start">
                <CardTitle>Statistiken</CardTitle>
                <CardDescription>{dateRange}</CardDescription>
            </div>
            <Popover.Root bind:open={popoverOpen}>
                <Popover.Trigger>
                    {#snippet child({ props })}
                        <Button
                            {...props}
                            variant="outline"
                            class="w-44 justify-between rounded-lg sm:ms-auto"
                            aria-label="Zeitraum auswählen"
                        >
                            {timeRangeLabel}
                            <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 size-4 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </Button>
                    {/snippet}
                </Popover.Trigger>
                <Popover.Content class="w-52 p-1" align="end">
                    {#each presets as preset (preset.value)}
                        <button
                            class="flex w-full items-center justify-between rounded-md px-3 py-1.5 text-sm transition-colors hover:bg-accent {timeRange === preset.value ? 'font-medium' : ''}"
                            onclick={() => { timeRange = preset.value; popoverOpen = false; }}
                        >
                            {preset.label}
                            {#if timeRange === preset.value}
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                            {/if}
                        </button>
                    {/each}
                    <Separator class="my-1" />
                    <div class="px-3 py-2">
                        <p class="mb-2 text-xs font-medium text-muted-foreground">Benutzerdefiniert</p>
                        <div class="flex flex-col gap-1.5">
                            <input
                                type="date"
                                bind:value={customFrom}
                                class="h-8 w-full rounded-md border bg-transparent px-2 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
                            />
                            <input
                                type="date"
                                bind:value={customTo}
                                min={customFrom}
                                class="h-8 w-full rounded-md border bg-transparent px-2 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
                            />
                            <Button size="sm" class="mt-1 h-7 text-xs" onclick={applyCustomRange} disabled={!customFrom || !customTo}>
                                Anwenden
                            </Button>
                        </div>
                    </div>
                </Popover.Content>
            </Popover.Root>
        </CardHeader>
        <CardContent class="flex flex-col gap-6 pt-6">
            <div>
                <p class="mb-3 text-sm font-medium">Bestellungen</p>
                <ChartContainer
                    config={ordersConfig}
                    class="aspect-auto h-50 w-full"
                >
                    <AreaChart
                        data={filteredData}
                        x="date"
                        xScale={scaleUtc()}
                        series={[
                            {
                                key: 'orders',
                                label: 'Bestellungen',
                                color: ordersConfig.orders.color,
                            },
                        ]}
                        padding={{ left: 36 }}
                        props={{
                            xAxis: {
                                ticks: timeRange === '7d' ? 7 : undefined,
                                format: (v: Date) =>
                                    v.toLocaleDateString('de-DE', {
                                        month: 'short',
                                        day: 'numeric',
                                    }),
                            },
                            yAxis: { format: (v: number) => `${v}` },
                        }}
                    >
                        {#snippet marks({ context }: { context: any })}
                            <defs>
                                <linearGradient
                                    id="fillOrders"
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="5%"
                                        stop-color="var(--color-orders)"
                                        stop-opacity={1.0}
                                    />
                                    <stop
                                        offset="95%"
                                        stop-color="var(--color-orders)"
                                        stop-opacity={0.1}
                                    />
                                </linearGradient>
                            </defs>
                            <ChartClipPath
                                initialWidth={0}
                                motion={{
                                    width: {
                                        type: 'tween',
                                        duration: 1000,
                                        easing: cubicInOut,
                                    },
                                }}
                            >
                                {#each context.series.visibleSeries as s (s.key)}
                                    <Area
                                        seriesKey={s.key}
                                        curve={curveNatural}
                                        fillOpacity={0.4}
                                        line={{ class: 'stroke-1' }}
                                        motion="tween"
                                        {...s.props}
                                        fill="url(#fillOrders)"
                                    />
                                {/each}
                            </ChartClipPath>
                        {/snippet}
                    </AreaChart>
                </ChartContainer>
            </div>

            <div class="border-t"></div>

            <div>
                <p class="mb-3 text-sm font-medium">Umsatz</p>
                <ChartContainer
                    config={revenueConfig}
                    class="aspect-auto h-50 w-full"
                >
                    <AreaChart
                        data={filteredData}
                        x="date"
                        xScale={scaleUtc()}
                        series={[
                            {
                                key: 'revenue',
                                label: 'Umsatz',
                                color: revenueConfig.revenue.color,
                            },
                        ]}
                        padding={{ left: 64 }}
                        props={{
                            xAxis: {
                                ticks: timeRange === '7d' ? 7 : undefined,
                                format: (v: Date) =>
                                    v.toLocaleDateString('de-DE', {
                                        month: 'short',
                                        day: 'numeric',
                                    }),
                            },
                            yAxis: {
                                format: (v: number) =>
                                    new Intl.NumberFormat('de-DE', {
                                        style: 'currency',
                                        currency: 'EUR',
                                        maximumFractionDigits: 0,
                                    }).format(v),
                            },
                        }}
                    >
                        {#snippet marks({ context }: { context: any })}
                            <defs>
                                <linearGradient
                                    id="fillRevenue"
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2="1"
                                >
                                    <stop
                                        offset="5%"
                                        stop-color="var(--color-revenue)"
                                        stop-opacity={1.0}
                                    />
                                    <stop
                                        offset="95%"
                                        stop-color="var(--color-revenue)"
                                        stop-opacity={0.1}
                                    />
                                </linearGradient>
                            </defs>
                            <ChartClipPath
                                initialWidth={0}
                                motion={{
                                    width: {
                                        type: 'tween',
                                        duration: 1000,
                                        easing: cubicInOut,
                                    },
                                }}
                            >
                                {#each context.series.visibleSeries as s (s.key)}
                                    <Area
                                        seriesKey={s.key}
                                        curve={curveNatural}
                                        fillOpacity={0.4}
                                        line={{ class: 'stroke-1' }}
                                        motion="tween"
                                        {...s.props}
                                        fill="url(#fillRevenue)"
                                    />
                                {/each}
                            </ChartClipPath>
                        {/snippet}
                    </AreaChart>
                </ChartContainer>
            </div>
        </CardContent>
    </Card>
</div>
