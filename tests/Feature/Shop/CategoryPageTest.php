<?php

namespace Tests\Feature\Shop;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_page_lists_only_its_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(2)->for($category)->create();
        Product::factory()->create();

        $this->get(route('category.show', $category))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Products/ByCategory')
                    ->where('category.id', $category->id)
                    ->where('category.slug', $category->slug)
                    ->where('total', 2)
            );
    }

    public function test_category_page_accepts_sort_option(): void
    {
        $category = Category::factory()->create();

        $this->get(route('category.show', ['category' => $category->slug, 'sort' => 'price_asc']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('sort', 'price_asc'));
    }

    public function test_unknown_category_returns_404(): void
    {
        $this->get('/gibt-es-nicht')->assertNotFound();
    }
}
