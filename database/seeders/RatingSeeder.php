<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Demo-Bewertungen fuer "Druckerpapier UPP-110S", damit dieses Produkt beim
 * lokalen Entwickeln immer eine gefuellte Bewertungsansicht zeigt. Nur fuer
 * Dev/Demo (siehe DatabaseSeeder) - kein Produktions-Content.
 */
class RatingSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $product = Product::where('name', 'Druckerpapier UPP-110S')->first();

        if (! $product) {
            $this->command?->warn('RatingSeeder: Produkt "Druckerpapier UPP-110S" nicht gefunden, ueberspringe.');

            return;
        }

        $reviews = [
            [
                'stars' => 5,
                'content' => 'Zuverlässige Qualität, druckt gestochen scharf und ohne Streifen. Bestellen wir jetzt regelmäßig nach.',
                'created_at' => now()->subDays(45),
            ],
            [
                'stars' => 4,
                'content' => 'Gutes Papier für den täglichen Praxisbedarf. Lieferung war schnell, Verpackung könnte etwas stabiler sein.',
                'created_at' => now()->subDays(30),
            ],
            [
                'stars' => 5,
                'content' => 'Passt exakt in unseren Ultraschalldrucker, keine Papierstaus mehr seit der Umstellung auf dieses Papier.',
                'created_at' => now()->subDays(18),
            ],
            [
                'stars' => 3,
                'content' => 'Solide Qualität, aber im Vergleich zum Vorgängerprodukt spürbar teurer geworden.',
                'created_at' => now()->subDays(7),
            ],
        ];

        foreach ($reviews as $review) {
            // created_at is intentionally not fillable on Rating - set it
            // explicitly so the demo reviews have varied, believable dates
            // instead of all sharing the seeder's run timestamp.
            $rating = $product->ratings()->updateOrCreate(
                ['content' => $review['content']],
                ['stars' => $review['stars']],
            );
            $rating->forceFill(['created_at' => $review['created_at']])->save();
        }
    }
}
