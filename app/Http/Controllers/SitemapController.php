<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), function () {
            $staticUrls = [
                ['loc' => url('/'), 'changefreq' => 'weekly', 'priority' => '1.0'],
                ['loc' => url('/products'), 'changefreq' => 'daily', 'priority' => '0.9'],
                ['loc' => url('/faq'), 'changefreq' => 'monthly', 'priority' => '0.7'],
                ['loc' => url('/agb'), 'changefreq' => 'monthly', 'priority' => '0.3'],
                ['loc' => url('/impressum'), 'changefreq' => 'monthly', 'priority' => '0.3'],
                ['loc' => url('/datenschutz'), 'changefreq' => 'monthly', 'priority' => '0.3'],
                ['loc' => url('/widerrufsbelehrung'), 'changefreq' => 'monthly', 'priority' => '0.3'],
                ['loc' => url('/versand'), 'changefreq' => 'monthly', 'priority' => '0.4'],
                ['loc' => url('/zahlung'), 'changefreq' => 'monthly', 'priority' => '0.4'],
            ];

            $products = Product::select('id', 'updated_at')->get();
            $categories = Category::select('slug', 'updated_at')->get();

            return view('sitemap', [
                'staticUrls' => $staticUrls,
                'products' => $products,
                'categories' => $categories,
            ])->render();
        });

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
