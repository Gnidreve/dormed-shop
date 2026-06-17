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
    import * as Select from '@/components/ui/select';
    import PlaceholderPattern from '@/components/PlaceholderPattern.svelte';
    import { scaleUtc } from 'd3-scale';
    import { Area, AreaChart, ChartClipPath } from 'layerchart';
    import { curveNatural } from 'd3-shape';
    import { cubicInOut } from 'svelte/easing';
    import * as AdminLoginController from '@/actions/App/Http/Controllers/Admin/LoginController';

    const admin = $derived((page.props.auth as any).admin);

    function logout() {
        router.post(AdminLoginController.logout.url());
    }

    function seededRandom(seed: number) {
        let s = seed;
        return () => {
            s = ((s * 1664525 + 1013904223) | 0) >>> 0;
            return s / 0x100000000;
        };
    }

    const baseDate = new Date('2026-06-17');

    const allData = (() => {
        const rOrders = seededRandom(42);
        const rRevenue = seededRandom(137);
        const result = [];
        for (let i = 89; i >= 0; i--) {
            const d = new Date(baseDate);
            d.setDate(d.getDate() - i);
            result.push({
                date: d,
                orders: Math.round(rOrders() * 25),
                revenue: Math.round(rRevenue() * 2500),
            });
        }
        return result;
    })();

    let timeRange = $state('30d');

    const timeRangeLabel = $derived(
        timeRange === '7d'
            ? 'Letzte 7 Tage'
            : timeRange === '30d'
              ? 'Letzte 30 Tage'
              : 'Letzte 3 Monate',
    );

    const filteredData = $derived.by(() => {
        const days = timeRange === '7d' ? 7 : timeRange === '30d' ? 30 : 90;
        const cutoff = new Date(baseDate);
        cutoff.setDate(cutoff.getDate() - days);
        return allData.filter((d) => d.date >= cutoff);
    });

    const dateRange = $derived.by(() => {
        if (!filteredData.length) return '';
        const fmt = (d: Date) =>
            d.toLocaleDateString('de-DE', { day: 'numeric', month: 'short' });
        return `${fmt(filteredData[0].date)} – ${fmt(filteredData[filteredData.length - 1].date)}`;
    });

    const ordersConfig = {
        orders: { label: 'Bestellungen', color: 'var(--chart-1)' },
    } satisfies ChartConfig;

    const revenueConfig = {
        revenue: { label: 'Umsatz', color: 'var(--chart-2)' },
    } satisfies ChartConfig;

    const fmtCurrency = new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
    });
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

    <Card>
        <CardHeader class="flex items-center gap-2 space-y-0 border-b py-5 sm:flex-row">
            <div class="grid flex-1 gap-1 text-center sm:text-start">
                <CardTitle>Statistiken</CardTitle>
                <CardDescription>{dateRange}</CardDescription>
            </div>
            <Select.Root type="single" bind:value={timeRange}>
                <Select.Trigger
                    class="w-40 rounded-lg sm:ms-auto"
                    aria-label="Zeitraum auswählen"
                >
                    {timeRangeLabel}
                </Select.Trigger>
                <Select.Content class="rounded-xl">
                    <Select.Item value="90d" class="rounded-lg"
                        >Letzte 3 Monate</Select.Item
                    >
                    <Select.Item value="30d" class="rounded-lg"
                        >Letzte 30 Tage</Select.Item
                    >
                    <Select.Item value="7d" class="rounded-lg"
                        >Letzte 7 Tage</Select.Item
                    >
                </Select.Content>
            </Select.Root>
        </CardHeader>
        <CardContent class="flex flex-col gap-6 pt-6">
            <div>
                <p class="mb-3 text-sm font-medium">Bestellungen</p>
                <ChartContainer
                    config={ordersConfig}
                    class="-ml-3 aspect-auto h-50 w-full"
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
                    class="-ml-3 aspect-auto h-50 w-full"
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
                                format: (v: number) => fmtCurrency.format(v),
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
