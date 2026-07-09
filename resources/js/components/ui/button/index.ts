import type { HTMLButtonAttributes } from 'svelte/elements';
import type { WithElementRef } from '@/lib/utils';
import type { ButtonSize, ButtonVariant } from './variants';

export { default as Button } from './Button.svelte';
export {
    buttonVariants,
    type ButtonSize,
    type ButtonVariant,
} from './variants';

/**
 * Prop shape the shadcn kit components expect when re-wrapping the Button
 * (carousel, form, ...). The local Button accepts these plus asChild.
 */
export type ButtonProps = WithElementRef<HTMLButtonAttributes> & {
    variant?: ButtonVariant;
    size?: ButtonSize;
};

export type Props = ButtonProps;
