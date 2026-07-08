<script lang="ts">
    import { Badge } from '@/components/ui/badge';
    import { orderStatusBadgeClass, orderStatusLabel } from '@/lib/orderStatus';

    let { status }: { status: string } = $props();
</script>

<Badge
    variant="outline"
    class="{orderStatusBadgeClass(status)} border-transparent"
>
    {orderStatusLabel(status)}
</Badge>
