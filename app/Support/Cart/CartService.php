<?php

namespace App\Support\Cart;

use App\Contracts\CartStore;
use App\Models\Product;
use Illuminate\Support\Collection;

class CartService
{
    public const DEFAULT_ADDRESS = [
        'company' => '',
        'salutation' => '',
        'first_name' => '',
        'last_name' => '',
        'street' => '',
        'house_number' => '',
        'address_line2' => '',
        'zip' => '',
        'city' => '',
        'country' => 'DE',
        'phone' => '',
    ];

    public function __construct(
        private readonly CartStore $store,
    ) {}

    public function cart(): array
    {
        $state = $this->state();
        $items = $this->items($state['items']);

        if ($items->isEmpty() && $state['items'] !== []) {
            $state['items'] = [];
            $this->persist($state);
        }

        $shippingMethods = $this->shippingMethods($state['shipping_method']);
        $paymentMethods = $this->paymentMethods($state['payment_method']);
        $selectedShipping = collect($shippingMethods)->firstWhere('selected', true);
        $selectedPayment = collect($paymentMethods)->firstWhere('selected', true);

        $subtotalCents = $items->sum('line_total_cents');
        $shippingCents = (int) ($selectedShipping['price_cents'] ?? 0);
        $totalCents = $subtotalCents + $shippingCents;
        $vatRate = (int) config('shop.cart.vat_rate', 19);
        $netTotalCents = (int) round($totalCents / (1 + ($vatRate / 100)));
        $vatAmountCents = $totalCents - $netTotalCents;

        return [
            'items' => $items->values()->all(),
            'count' => $items->sum('quantity'),
            'is_empty' => $items->isEmpty(),
            'vat_rate' => $vatRate,
            'shipping_methods' => $shippingMethods,
            'payment_methods' => $paymentMethods,
            'selected_shipping_method' => $selectedShipping,
            'selected_payment_method' => $selectedPayment,
            'subtotal' => $this->formatAmount($subtotalCents),
            'shipping_total' => $this->formatAmount($shippingCents),
            'total' => $this->formatAmount($totalCents),
            'net_total' => $this->formatAmount($netTotalCents),
            'vat_amount' => $this->formatAmount($vatAmountCents),
            'shipping_address' => $this->getShippingAddress(),
            'billing_address' => $this->getBillingAddress(),
        ];
    }

    public function add(Product $product, int $quantity): void
    {
        $state = $this->state();
        $productId = (string) $product->getKey();
        $existingItem = $state['items'][$productId] ?? [];

        $state['items'][$productId] = [
            'quantity' => min(99, ((int) ($existingItem['quantity'] ?? 0)) + $quantity),
            'unit_price' => (string) ($existingItem['unit_price'] ?? $product->price),
            'name' => (string) ($existingItem['name'] ?? $product->name),
            'product_number' => (string) ($existingItem['product_number'] ?? $product->id),
        ];

        $this->persist($state);
    }

    public function updateQuantity(Product $product, int $quantity): void
    {
        $state = $this->state();
        $productId = (string) $product->getKey();

        if (! isset($state['items'][$productId])) {
            $this->add($product, $quantity);

            return;
        }

        $state['items'][$productId]['quantity'] = min(99, max(1, $quantity));

        $this->persist($state);
    }

    public function remove(Product $product): void
    {
        $state = $this->state();
        unset($state['items'][(string) $product->getKey()]);

        $this->persist($state);
    }

    public function setShippingMethod(string $shippingMethod): void
    {
        $state = $this->state();
        $state['shipping_method'] = $shippingMethod;

        $this->persist($state);
    }

    public function setPaymentMethod(string $paymentMethod): void
    {
        $state = $this->state();
        $state['payment_method'] = $paymentMethod;

        $this->persist($state);
    }

    public function setShippingAddress(array $data): void
    {
        $state = $this->state();
        $state['shipping_address'] = array_merge(self::DEFAULT_ADDRESS, $data);

        $this->persist($state);
    }

    public function setBillingAddress(?array $data): void
    {
        $state = $this->state();
        $state['billing_address'] = $data !== null
            ? array_merge(self::DEFAULT_ADDRESS, $data)
            : null;

        $this->persist($state);
    }

    public function getShippingAddress(): array
    {
        return $this->state()['shipping_address'] ?? self::DEFAULT_ADDRESS;
    }

    public function getBillingAddress(): ?array
    {
        return $this->state()['billing_address']; // null = same as shipping
    }

    public function clear(): void
    {
        $this->persist([
            'items' => [],
            'shipping_method' => (string) collect(config('shop.cart.shipping_methods', []))->pluck('id')->first(),
            'payment_method' => (string) collect(config('shop.cart.payment_methods', []))->pluck('id')->first(),
            'shipping_address' => self::DEFAULT_ADDRESS,
            'billing_address' => null,
        ]);
    }

    private function items(array $rawItems): Collection
    {
        $productIds = array_map('intval', array_keys($rawItems));

        if ($productIds === []) {
            return collect();
        }

        $products = Product::query()
            ->whereKey($productIds)
            ->orderBy('name')
            ->get()
            ->keyBy(fn (Product $product) => (string) $product->getKey());

        return collect($rawItems)
            ->map(function (array $item, string $productId) use ($products): ?array {
                /** @var Product|null $product */
                $product = $products->get($productId);
                $quantity = (int) ($item['quantity'] ?? 0);

                if ($quantity < 1) {
                    return null;
                }

                $unitPriceCents = $this->amountToCents($item['unit_price'] ?? $product?->price ?? 0);
                $lineTotalCents = $unitPriceCents * $quantity;

                return [
                    'product_id' => (int) $productId,
                    'name' => (string) ($item['name'] ?? $product?->name ?? 'Produkt nicht verfügbar'),
                    'description' => $product?->description,
                    'product_number' => (string) ($item['product_number'] ?? $productId),
                    'quantity' => $quantity,
                    'unit_price' => $this->formatAmount($unitPriceCents),
                    'line_total' => $this->formatAmount($lineTotalCents),
                    'line_total_cents' => $lineTotalCents,
                    'product_url' => $product ? route('products.show', $product) : route('products.index'),
                    'is_available' => $product !== null,
                ];
            })
            ->filter();
    }

    private function shippingMethods(string $selectedId): array
    {
        return collect(config('shop.cart.shipping_methods', []))
            ->values()
            ->map(function (array $method, int $index) use ($selectedId): array {
                $methodId = (string) $method['id'];
                $priceCents = $this->amountToCents($method['price']);

                return [
                    'id' => $methodId,
                    'label' => $method['label'],
                    'description' => $method['description'] ?? null,
                    'price' => $this->formatAmount($priceCents),
                    'price_cents' => $priceCents,
                    'selected' => $methodId === $selectedId || ($selectedId === '' && $index === 0),
                ];
            })
            ->all();
    }

    private function paymentMethods(string $selectedId): array
    {
        $methods = [];
        $index = 0;

        foreach (config('shop.cart.providers', []) as $provider => $config) {
            if (! ($config['enabled'] ?? false)) {
                continue;
            }

            foreach ($config['methods'] ?? [] as $method) {
                $methodId = (string) $method['id'];

                $methods[] = [
                    'id' => $methodId,
                    'provider' => $provider,
                    'label' => $method['label'],
                    'description' => $method['description'] ?? null,
                    'selected' => $methodId === $selectedId || ($selectedId === '' && $index === 0),
                ];

                $index++;
            }
        }

        return $methods;
    }

    public function isAddressComplete(): bool
    {
        $a = $this->getShippingAddress();

        return ! empty($a['first_name'])
            && ! empty($a['last_name'])
            && ! empty($a['street'])
            && ! empty($a['house_number'])
            && ! empty($a['zip'])
            && ! empty($a['city']);
    }

    private function state(): array
    {
        $rawState = $this->store->get();
        $shippingMethodIds = collect(config('shop.cart.shipping_methods', []))->pluck('id')->map(fn ($id) => (string) $id)->all();
        $paymentMethodIds = collect($this->paymentMethods(''))->pluck('id')->map(fn ($id) => (string) $id)->all();

        return [
            'items' => collect($rawState['items'] ?? [])
                ->mapWithKeys(function (mixed $item, mixed $productId): array {
                    $normalizedProductId = (int) $productId;
                    $normalizedQuantity = (int) data_get($item, 'quantity', is_array($item) ? null : $item);

                    if ($normalizedProductId < 1 || $normalizedQuantity < 1) {
                        return [];
                    }

                    return [
                        (string) $normalizedProductId => [
                            'quantity' => min(99, $normalizedQuantity),
                            'unit_price' => (string) data_get($item, 'unit_price', '0.00'),
                            'name' => (string) data_get($item, 'name', ''),
                            'product_number' => (string) data_get($item, 'product_number', $normalizedProductId),
                        ],
                    ];
                })
                ->all(),
            'shipping_method' => in_array(($rawState['shipping_method'] ?? null), $shippingMethodIds, true)
                ? (string) $rawState['shipping_method']
                : (string) ($shippingMethodIds[0] ?? ''),
            'payment_method' => in_array(($rawState['payment_method'] ?? null), $paymentMethodIds, true)
                ? (string) $rawState['payment_method']
                : (string) ($paymentMethodIds[0] ?? ''),
            'shipping_address' => array_merge(
                self::DEFAULT_ADDRESS,
                $rawState['shipping_address'] ?? []
            ),
            'billing_address' => isset($rawState['billing_address']) && is_array($rawState['billing_address'])
                ? array_merge(self::DEFAULT_ADDRESS, $rawState['billing_address'])
                : null,
        ];
    }

    private function persist(array $state): void
    {
        $this->store->put($state);
    }

    private function amountToCents(string|int|float|null $amount): int
    {
        $normalized = str_replace(',', '.', (string) $amount);
        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function formatAmount(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
