<?php

namespace App\Support\Cart;

use App\Contracts\CartStore;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\ShippingMethod;
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

    /**
     * Lazily loaded and reused for the lifetime of this instance, so a
     * single cart() call doesn't query shipping methods twice (once via
     * state() for the selected id, once via shippingMethods() for the list).
     */
    private ?Collection $shippingMethodModelsCache = null;

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

    public function add(Product $product, int $quantity, ?ProductVariant $variant = null): void
    {
        $state = $this->state();
        $lineKey = $this->lineKey((int) $product->getKey(), $variant?->getKey());
        $existingQuantity = (int) ($state['items'][$lineKey] ?? 0);

        $state['items'][$lineKey] = min(99, $existingQuantity + $quantity);

        $this->persist($state);
    }

    public function updateQuantity(Product $product, int $quantity, ?ProductVariant $variant = null): void
    {
        $state = $this->state();
        $state['items'][$this->lineKey((int) $product->getKey(), $variant?->getKey())] = min(99, max(1, $quantity));

        $this->persist($state);
    }

    public function remove(Product $product, ?ProductVariant $variant = null): void
    {
        $state = $this->state();
        unset($state['items'][$this->lineKey((int) $product->getKey(), $variant?->getKey())]);

        $this->persist($state);
    }

    /**
     * Cart lines are keyed "productId" (no variant) or "productId:variantId",
     * so the same product can sit in the cart once per variant.
     */
    private function lineKey(int $productId, ?int $variantId): string
    {
        return $variantId ? "{$productId}:{$variantId}" : (string) $productId;
    }

    /**
     * @return array{0: int, 1: int|null} [productId, variantId]
     */
    private function parseLineKey(string $lineKey): array
    {
        [$productId, $variantId] = array_pad(explode(':', $lineKey, 2), 2, null);

        return [(int) $productId, ((int) $variantId) > 0 ? (int) $variantId : null];
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
            'shipping_method' => (string) ($this->shippingMethodModels()->first()?->id ?? ''),
            'payment_method' => (string) (collect($this->paymentMethods(''))->first()['id'] ?? ''),
            'shipping_address' => self::DEFAULT_ADDRESS,
            'billing_address' => null,
        ]);
    }

    private function items(array $rawItems): Collection
    {
        if ($rawItems === []) {
            return collect();
        }

        $productIds = collect(array_keys($rawItems))
            ->map(fn (string $lineKey): int => $this->parseLineKey($lineKey)[0])
            ->unique()
            ->all();

        $products = Product::query()
            ->with('manufacturer', 'variants')
            ->with(['images' => fn ($query) => $query->where('sort_order', 0)])
            ->whereKey($productIds)
            ->get()
            ->keyBy(fn (Product $product) => (string) $product->getKey());

        return collect($rawItems)
            ->map(function (int $quantity, string $lineKey) use ($products): ?array {
                [$productId, $variantId] = $this->parseLineKey($lineKey);

                /** @var Product|null $product */
                $product = $products->get((string) $productId);

                if ($quantity < 1) {
                    return null;
                }

                /** @var ProductVariant|null $variant */
                $variant = $variantId !== null ? $product?->variants->firstWhere('id', $variantId) : null;
                // A chosen variant that no longer exists makes the line unbuyable.
                $variantMissing = $variantId !== null && $variant === null;

                $unitPriceCents = $this->amountToCents($variant->price ?? $product->price ?? 0);
                $lineTotalCents = $unitPriceCents * $quantity;

                $name = (string) ($product?->name ?? 'Produkt nicht verfügbar');

                // The variant label becomes part of the line name so cart UI,
                // checkout, order snapshot and mails all show it consistently.
                if ($variant !== null) {
                    $name .= ' – '.$variant->label;
                }

                return [
                    'line_key' => $lineKey,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'variant_label' => $variant?->label,
                    'name' => $name,
                    'description' => $product?->description,
                    'product_number' => (string) ($product?->id ?? $productId),
                    'manufacturer_name' => $product?->manufacturer?->name,
                    'quantity' => $quantity,
                    'unit_price' => $this->formatAmount($unitPriceCents),
                    'line_total' => $this->formatAmount($lineTotalCents),
                    'line_total_cents' => $lineTotalCents,
                    'image_url' => $product?->images->first()?->url,
                    'product_url' => $product ? route('products.show', $product) : route('products.index'),
                    'is_available' => $product !== null && $product->is_available && ! $variantMissing,
                ];
            })
            ->filter();
    }

    private function shippingMethodModels(): Collection
    {
        return $this->shippingMethodModelsCache ??= ShippingMethod::orderBy('sort_order')->get();
    }

    private function shippingMethods(string $selectedId): array
    {
        return $this->shippingMethodModels()
            ->values()
            ->map(function (ShippingMethod $method, int $index) use ($selectedId): array {
                $methodId = (string) $method->id;
                $priceCents = $this->amountToCents($method->price);

                return [
                    'id' => $methodId,
                    'label' => $method->name,
                    'description' => $method->description,
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
        $activeProvider = Setting::get('payment.provider') ?? 'paypal';
        $providers = config('shop.cart.providers', []);

        // Invoice is always available; PayPal is shown additionally when active.
        $visibleProviders = array_unique(array_filter(['invoice', $activeProvider]));

        foreach ($visibleProviders as $provider) {
            $config = $providers[$provider] ?? [];

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

    public function hasUnavailableItems(): bool
    {
        return $this->items($this->state()['items'])
            ->contains(fn (array $item): bool => ! $item['is_available']);
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
        $shippingMethodIds = $this->shippingMethodModels()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $paymentMethodIds = collect($this->paymentMethods(''))->pluck('id')->map(fn ($id) => (string) $id)->all();

        return [
            'items' => collect($rawState['items'] ?? [])
                ->mapWithKeys(function (mixed $item, mixed $lineKey): array {
                    [$productId, $variantId] = $this->parseLineKey((string) $lineKey);
                    // Older sessions stored a {quantity, unit_price, name, product_number}
                    // shape per item; only the quantity still matters going forward.
                    $normalizedQuantity = (int) (is_array($item) ? ($item['quantity'] ?? 0) : $item);

                    if ($productId < 1 || $normalizedQuantity < 1) {
                        return [];
                    }

                    return [$this->lineKey($productId, $variantId) => min(99, $normalizedQuantity)];
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
