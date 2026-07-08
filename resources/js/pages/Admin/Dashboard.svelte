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
    import { scaleUtc } from 'd3-scale';
    import { curveNatural } from 'd3-shape';
    import { Area, AreaChart, ChartClipPath } from 'layerchart';
    import TrendingDown from 'lucide-svelte/icons/trending-down';
    import TrendingUp from 'lucide-svelte/icons/trending-up';
    import { cubicInOut } from 'svelte/easing';
    import AppHead from '@/components/AppHead.svelte';
    import { Button } from '@/components/ui/button';
    import {
        Card,
        CardHeader,
        CardTitle,
        CardDescription,
        CardContent,
        CardFooter,
    } from '@/components/ui/card';
    import { ChartContainer, ChartTooltip } from '@/components/ui/chart';
    import type { ChartConfig } from '@/components/ui/chart';
    import * as Popover from '@/components/ui/popover';
    import { Separator } from '@/components/ui/separator';
    import { formatPrice } from '@/lib/currency';
    import { formatDate } from '@/lib/date';

    type ChartEntry = { date: string; orders: number; revenue: number };

    let { chartData }: { chartData: ChartEntry[] } = $props();

    const allData = $derived(
        chartData.map((d) => ({ ...d, date: new Date(d.date) })),
    );

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

    const timeRangeLabel = $derived.by(() => {
        if (timeRange === 'custom' && customFrom && customTo) {
            return `${formatDate(customFrom)} – ${formatDate(customTo)}`;
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
        const cutoff = new Date(
            Date.UTC(
                new Date().getUTCFullYear(),
                new Date().getUTCMonth(),
                new Date().getUTCDate() - days,
            ),
        );

        return allData.filter((d) => d.date >= cutoff);
    });

    const dateRange = $derived.by(() => {
        if (!filteredData.length) {
            return '';
        }

        return `${formatDate(filteredData[0].date)} – ${formatDate(filteredData[filteredData.length - 1].date)}`;
    });

    function applyCustomRange() {
        if (customFrom && customTo) {
            timeRange = 'custom';
            popoverOpen = false;
        }
    }

    // Orders (small integers, e.g. 0-50) and revenue (EUR, e.g. 0-5000) live
    // on wildly different scales. Stacked on one shared, hidden y-axis, the
    // order line would otherwise be visually flattened to nothing. Scale
    // orders up to revenue's magnitude for plotting only - the tooltip
    // formatter divides back out to show the real order count.
    const scaleFactor = $derived.by(() => {
        const maxOrders = Math.max(0, ...filteredData.map((d) => d.orders));
        const maxRevenue = Math.max(0, ...filteredData.map((d) => d.revenue));

        return maxOrders > 0 ? maxRevenue / maxOrders : 1;
    });

    const plotData = $derived(
        filteredData.map((d) => ({
            ...d,
            ordersScaled: d.orders * scaleFactor,
        })),
    );

    const COLOR_ORDERS = '#3f3f46';
    const COLOR_REVENUE = '#a1a1aa';

    const chartConfig = {
        ordersScaled: { label: 'Bestellungen', color: COLOR_ORDERS },
        revenue: { label: 'Umsatz', color: COLOR_REVENUE },
    } satisfies ChartConfig;

    // Trend footer: selected period's revenue vs. the immediately preceding
    // period of the same length (e.g. last 7 days vs. the 7 days before
    // that). Server sends 180 days so this stays available client-side even
    // for the 90-day preset.
    const previousPeriodRevenue = $derived.by(() => {
        if (!filteredData.length) {
            return null;
        }

        const spanDays = filteredData.length;
        const firstDate = filteredData[0].date;
        const dayMs = 24 * 60 * 60 * 1000;
        const prevEnd = new Date(firstDate.getTime() - dayMs);
        const prevStart = new Date(firstDate.getTime() - spanDays * dayMs);

        const prevData = allData.filter(
            (d) => d.date >= prevStart && d.date <= prevEnd,
        );

        if (prevData.length < spanDays) {
            // Not enough history loaded to compare fairly (e.g. custom range
            // reaching close to the 180-day window edge).
            return null;
        }

        return prevData.reduce((sum, d) => sum + d.revenue, 0);
    });

    const currentPeriodRevenue = $derived(
        filteredData.reduce((sum, d) => sum + d.revenue, 0),
    );

    const revenueTrendPercent = $derived.by(() => {
        if (previousPeriodRevenue === null || previousPeriodRevenue === 0) {
            return null;
        }

        return (
            ((currentPeriodRevenue - previousPeriodRevenue) /
                previousPeriodRevenue) *
            100
        );
    });
</script>

<AppHead title="Admin Dashboard" />

<div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
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
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="ml-2 size-4 opacity-50"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"><path d="m6 9 6 6 6-6" /></svg
                            >
                        </Button>
                    {/snippet}
                </Popover.Trigger>
                <Popover.Content class="w-52 p-1" align="end">
                    {#each presets as preset (preset.value)}
                        <button
                            class="flex w-full items-center justify-between rounded-md px-3 py-1.5 text-sm transition-colors hover:bg-accent {timeRange ===
                            preset.value
                                ? 'font-medium'
                                : ''}"
                            onclick={() => {
                                timeRange = preset.value;
                                popoverOpen = false;
                            }}
                        >
                            {preset.label}
                            {#if timeRange === preset.value}
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="size-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    ><path d="M20 6 9 17l-5-5" /></svg
                                >
                            {/if}
                        </button>
                    {/each}
                    <Separator class="my-1" />
                    <div class="px-3 py-2">
                        <p
                            class="mb-2 text-xs font-medium text-muted-foreground"
                        >
                            Benutzerdefiniert
                        </p>
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
                            <Button
                                size="sm"
                                class="mt-1 h-7 text-xs"
                                onclick={applyCustomRange}
                                disabled={!customFrom || !customTo}
                            >
                                Anwenden
                            </Button>
                        </div>
                    </div>
                </Popover.Content>
            </Popover.Root>
        </CardHeader>
        <CardContent class="pt-6">
            <ChartContainer
                config={chartConfig}
                class="aspect-auto h-75 w-full"
            >
                <AreaChart
                    legend
                    data={plotData}
                    x="date"
                    xScale={scaleUtc()}
                    series={[
                        {
                            key: 'ordersScaled',
                            label: 'Bestellungen',
                            color: chartConfig.ordersScaled.color,
                        },
                        {
                            key: 'revenue',
                            label: 'Umsatz',
                            color: chartConfig.revenue.color,
                        },
                    ]}
                    seriesLayout="stack"
                    props={{
                        xAxis: {
                            ticks: timeRange === '7d' ? 7 : undefined,
                            format: (v: Date) => formatDate(v),
                        },
                        yAxis: { format: () => '' },
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
                                    stop-color="var(--color-ordersScaled)"
                                    stop-opacity={1.0}
                                />
                                <stop
                                    offset="95%"
                                    stop-color="var(--color-ordersScaled)"
                                    stop-opacity={0.1}
                                />
                            </linearGradient>
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
                                    fill={s.key === 'ordersScaled'
                                        ? 'url(#fillOrders)'
                                        : 'url(#fillRevenue)'}
                                />
                            {/each}
                        </ChartClipPath>
                    {/snippet}
                    {#snippet tooltip({ context }: { context: any })}
                        <ChartTooltip
                            {context}
                            indicator="line"
                            labelFormatter={(v: Date) => formatDate(v)}
                        >
                            {#snippet formatter({ value, name, item })}
                                <div
                                    class="flex w-full flex-1 items-stretch gap-2"
                                >
                                    <div
                                        class="w-1 shrink-0 rounded-[2px]"
                                        style="background:{item.color};"
                                    ></div>
                                    <div
                                        class="flex flex-1 items-center justify-between"
                                    >
                                        <span class="text-muted-foreground"
                                            >{name}</span
                                        >
                                        <span
                                            class="font-mono font-medium tabular-nums"
                                        >
                                            {item.key === 'revenue'
                                                ? formatPrice(value as number)
                                                : Math.round(
                                                      (value as number) /
                                                          scaleFactor,
                                                  )}
                                        </span>
                                    </div>
                                </div>
                            {/snippet}
                        </ChartTooltip>
                    {/snippet}
                </AreaChart>
            </ChartContainer>
        </CardContent>
        {#if revenueTrendPercent !== null}
            <CardFooter>
                <div
                    class="flex items-center gap-2 text-sm font-medium leading-none"
                >
                    Umsatz {revenueTrendPercent >= 0 ? 'steigt' : 'sinkt'} um {Math.abs(
                        revenueTrendPercent,
                    ).toFixed(1)}&nbsp;% im gewählten Zeitraum im Vergleich zur
                    Vorperiode
                    {#if revenueTrendPercent >= 0}
                        <TrendingUp class="size-4" />
                    {:else}
                        <TrendingDown class="size-4" />
                    {/if}
                </div>
            </CardFooter>
        {/if}
    </Card>
</div>
