<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\Address\AddressRules;
use App\Support\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AddressController extends Controller
{
    public function edit(Request $request): Response|JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $shipping = $customer->defaultShippingAddress();
        $billing = $customer->defaultBillingAddress();

        // JSON für das User-Settings-Modal (lädt die Formulardaten per fetch).
        if ($request->wantsJson()) {
            return response()->json([
                'shipping' => $shipping?->toAddressArray(),
                'billing' => $billing?->toAddressArray(),
            ]);
        }

        return Inertia::render('settings/Addresses', [
            'shipping' => $shipping?->toAddressArray(),
            'billing' => $billing?->toAddressArray(),
        ]);
    }

    public function update(Request $request, CartService $cartService): RedirectResponse
    {
        $data = $request->validate([
            'billing_same_as_shipping' => ['boolean'],
            ...AddressRules::forPrefix('shipping'),
            ...AddressRules::forPrefix('billing', required: false),
        ]);

        /** @var Customer $customer */
        $customer = $request->user();
        $billingSame = (bool) ($data['billing_same_as_shipping'] ?? true);

        $shipping = $customer->defaultShippingAddress();

        $shippingData = array_merge($data['shipping'], ['type' => 'shipping', 'is_default' => true]);

        if ($shipping) {
            $shipping->update($shippingData);
        } else {
            $customer->addresses()->create($shippingData);
        }

        if ($billingSame) {
            $customer->addresses()->where('type', 'billing')->delete();
        } else {
            $billing = $customer->defaultBillingAddress();

            $billingData = array_merge($data['billing'] ?? [], ['type' => 'billing', 'is_default' => true]);

            if ($billing) {
                $billing->update($billingData);
            } else {
                $customer->addresses()->create($billingData);
            }
        }

        // Sync the cart if the shipping address there is still empty
        if (! $cartService->isAddressComplete()) {
            $cartService->setShippingAddress($data['shipping']);
            if (! $billingSame && ! empty($data['billing']['first_name'])) {
                $cartService->setBillingAddress($data['billing']);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Adressen gespeichert.']);

        return to_route('addresses.edit');
    }
}
