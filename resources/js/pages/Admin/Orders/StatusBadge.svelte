<script lang="ts">
    import { Badge } from '@/components/ui/badge';

    let { status, label }: { status: string; label: string } = $props();

    const statusClasses: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800 border-transparent',
        processing: 'bg-blue-100 text-blue-800 border-transparent',
        paid: 'bg-green-100 text-green-800 border-transparent',
        completed: 'bg-green-100 text-green-800 border-transparent',
        cancelled: 'bg-red-100 text-red-800 border-transparent',
        failed: 'bg-red-100 text-red-800 border-transparent',
        refunded: 'bg-red-100 text-red-800 border-transparent',
    };
</script>

<Badge
    variant="outline"
    class={statusClasses[status] ??
        'bg-muted text-muted-foreground border-transparent'}
>
    {label}
</Badge>
