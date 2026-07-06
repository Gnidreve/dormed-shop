<?php

namespace Tests\Feature\Support;

use App\Models\ShippingMethod;
use App\Support\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CartServiceQueryCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_only_queries_shipping_methods_once(): void
    {
        ShippingMethod::factory()->count(2)->create();

        DB::enableQueryLog();
        DB::flushQueryLog();

        app(CartService::class)->cart();

        $shippingMethodQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains($query['query'], 'shipping_methods'));

        $this->assertCount(1, $shippingMethodQueries);

        DB::disableQueryLog();
    }
}
