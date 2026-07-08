// Single source of truth for order status labels/colors — every place that
// renders an order status (admin + customer) reads from here instead of
// keeping its own copy, which drifted (different colors/labels per file).
export const ORDER_STATUSES = {
    pending: {
        label: 'Ausstehend',
        badgeClass: 'bg-yellow-100 text-yellow-700',
    },
    paid: { label: 'Bezahlt', badgeClass: 'bg-green-100 text-green-700' },
    cancelled: { label: 'Storniert', badgeClass: 'bg-red-100 text-red-700' },
    failed: { label: 'Fehlgeschlagen', badgeClass: 'bg-red-100 text-red-700' },
    refunded: { label: 'Erstattet', badgeClass: 'bg-red-100 text-red-700' },
} as const satisfies Record<string, { label: string; badgeClass: string }>;

export type OrderStatus = keyof typeof ORDER_STATUSES;

const FALLBACK_BADGE_CLASS = 'bg-muted text-muted-foreground';

export function orderStatusLabel(status: string): string {
    return ORDER_STATUSES[status as OrderStatus]?.label ?? status;
}

export function orderStatusBadgeClass(status: string): string {
    return (
        ORDER_STATUSES[status as OrderStatus]?.badgeClass ??
        FALLBACK_BADGE_CLASS
    );
}
