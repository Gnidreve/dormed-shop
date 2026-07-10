<?php

namespace Tests\Feature\Support;

use App\Models\Product;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_does_not_wipe_the_catalog_in_production(): void
    {
        $product = Product::factory()->create();

        // In Produktion muss der Seeder ohne SEED_PRODUCTS_FORCE abbrechen,
        // bevor er irgendetwas löscht (sonst wären der Katalog + alle Bilder
        // weg). Direkter run()-Aufruf, um den Guard ohne den
        // db:seed-Konsolen-Bestätigungsprompt zu prüfen.
        $this->app['env'] = 'production';

        (new ProductSeeder)->run();

        $this->assertModelExists($product);
    }
}
