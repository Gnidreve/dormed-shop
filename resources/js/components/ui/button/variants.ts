import { cn } from '@/lib/utils';

export type ButtonVariant =
    | 'default'
    | 'secondary'
    | 'ghost'
    | 'destructive'
    | 'outline'
    | 'link';

export type ButtonSize = 'default' | 'sm' | 'lg' | 'icon' | 'icon-sm';

const base =
    'inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50';

const variantClasses: Record<ButtonVariant, string> = {
    default: 'bg-primary text-primary-foreground shadow hover:bg-primary/90',
    secondary:
        'bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/80',
    ghost: 'hover:bg-accent hover:text-accent-foreground',
    destructive:
        'bg-destructive text-destructive-foreground shadow hover:bg-destructive/90',
    outline:
        'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
    link: 'text-primary underline-offset-4 hover:underline',
};

const sizeClasses: Record<ButtonSize, string> = {
    default: 'h-9 px-4 py-2',
    sm: 'h-8 rounded-md px-3 text-xs',
    lg: 'h-10 rounded-md px-8',
    icon: 'h-9 w-9',
    'icon-sm': 'h-8 w-8',
};

/**
 * Class builder in the call shape the shadcn kit components expect
 * (`buttonVariants({ variant, size, class })`), backed by the same class
 * maps the local Button.svelte renders with.
 */
export function buttonVariants(
    options: {
        variant?: ButtonVariant | null;
        size?: ButtonSize | null;
        class?: string | null;
    } = {},
): string {
    return cn(
        base,
        variantClasses[options.variant ?? 'default'],
        sizeClasses[options.size ?? 'default'],
        options.class,
    );
}
