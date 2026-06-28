<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import checkout from '@/routes/checkout';

    let {
        total: _total = 0,
        clientId = '',
        disabled: _disabled = false,
    }: {
        total?: number;
        clientId?: string;
        disabled?: boolean;
    } = $props();

    let containerId = $state(`paypal-button-${Math.random().toString(36).slice(2, 9)}`);
    let isProcessing = $state(false);
    let errorMessage = $state<string | null>(null);

    onMount(() => {
        loadPayPalSDK(clientId);
    });

    function loadPayPalSDK(clientId: string) {
        if (!clientId) {
            errorMessage = 'PayPal ist nicht konfiguriert (Client-ID fehlt).';

            return;
        }

        const script = document.createElement('script');
        script.src = `https://www.paypal.com/sdk/js?client-id=${clientId}&currency=EUR&locale=de_DE`;
        script.async = true;
        script.onload = () => renderButtons();
        script.onerror = () => {
            errorMessage = 'PayPal SDK konnte nicht geladen werden.';
        };
        document.head.appendChild(script);
    }

    function renderButtons() {
        if (typeof paypal === 'undefined') {
            errorMessage = 'PayPal SDK nicht verfügbar.';

            return;
        }

        const container = document.getElementById(containerId);

        if (!container) {
return;
}

        paypal
            .Buttons({
                style: {
                    layout: 'vertical',
                    color: 'blue',
                    shape: 'rect',
                    label: 'paypal',
                },
                createOrder: async (): Promise<string> => {
                    isProcessing = true;
                    errorMessage = null;

                    const response = await fetch('/paypal/order/create', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            Accept: 'application/json',
                        },
                        body: JSON.stringify({ agreed_to_terms: true }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        isProcessing = false;

                        throw new Error(data.error || 'PayPal-Order konnte nicht erstellt werden.');
                    }

                    return data.id;
                },
                onApprove: async (data: { orderID: string }) => {
                    isProcessing = true;

                    const response = await fetch('/paypal/order/capture', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            Accept: 'application/json',
                        },
                        body: JSON.stringify({ paypal_order_id: data.orderID }),
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        errorMessage = result.error || 'Zahlung konnte nicht abgeschlossen werden.';
                        isProcessing = false;

                        return;
                    }

                    // Redirect to success page
                    router.visit(checkout.success.url({ query: { paypal_order_id: data.orderID } }));
                },
                onCancel: () => {
                    errorMessage = 'Zahlung abgebrochen.';
                    isProcessing = false;
                },
                onError: (err: unknown) => {
                    errorMessage = 'Ein PayPal-Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
                    isProcessing = false;
                    console.error('PayPal error:', err);
                },
            })
            .render(`#${containerId}`);
    }

    function getCsrfToken(): string {
        const meta = document.querySelector('meta[name="csrf-token"]');

        return meta?.getAttribute('content') ?? '';
    }
</script>

<div class="paypal-button-wrapper">
    {#if errorMessage}
        <div class="mb-3 rounded-md bg-red-50 p-3 text-sm text-red-700">
            {errorMessage}
        </div>
    {/if}

    <div id={containerId} class="min-h-[40px]"></div>

    {#if isProcessing}
        <div class="mt-2 flex items-center justify-center gap-2">
            <div class="size-4 animate-spin rounded-full border-2 border-[#1a6bbf] border-t-transparent" />
            <span class="text-sm text-gray-600">Zahlung wird verarbeitet…</span>
        </div>
    {/if}
</div>
