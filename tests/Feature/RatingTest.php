<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Rating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RatingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Die anonyme Bewertungs-Route ist deaktiviert (S-1,
     * routes/public/rating.php) - reaktiviert wird sie GEMEINSAM mit dem
     * ebenfalls auskommentierten Produktdetail-Formular, dann order-scoped.
     * Diese beiden Submission-Tests bleiben als Grundlage bestehen und werden
     * mit der Route wieder scharf geschaltet (ggf. auf den Order-Flow
     * angepasst).
     */
    public function test_guest_can_submit_a_rating(): void
    {
        $this->markTestSkipped('Rating-Route deaktiviert (S-1) - reaktiviert gemeinsam mit dem order-basierten UI.');

        $product = Product::factory()->create();

        $this->post(route('ratings.store', $product), [
            'stars' => 5,
            'content' => 'Sehr gutes Produkt mit solider Verarbeitung.',
        ])->assertRedirect();

        $this->assertDatabaseHas('ratings', [
            'product_id' => $product->id,
            'stars' => 5,
            'content' => 'Sehr gutes Produkt mit solider Verarbeitung.',
        ]);
    }

    public function test_rating_submission_is_validated(): void
    {
        $this->markTestSkipped('Rating-Route deaktiviert (S-1) - reaktiviert gemeinsam mit dem order-basierten UI.');

        $product = Product::factory()->create();

        $this->from(route('products.show', $product))
            ->post(route('ratings.store', $product), [
                'stars' => 0,
                'content' => 'bad',
            ])
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHasErrors(['stars', 'content']);
    }

    public function test_product_page_contains_rating_summary_and_ratings(): void
    {
        $product = Product::factory()->create();

        Rating::factory()->for($product)->create([
            'stars' => 5,
            'content' => 'Ausgezeichnetes Gerät.',
        ]);

        Rating::factory()->for($product)->create([
            'stars' => 3,
            'content' => 'In Ordnung für den Preis.',
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Products/Show')
                ->where('ratings.0.stars', 3)
                ->where('ratings.1.stars', 5)
                ->where('ratingSummary.count', 2)
                ->where('ratingSummary.average', '4,0'));
    }
}
