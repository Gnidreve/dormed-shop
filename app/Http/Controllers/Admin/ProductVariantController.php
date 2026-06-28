<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_default' => ['boolean'],
        ]);

        if (! empty($data['is_default'])) {
            $product->variants()->update(['is_default' => false]);
        }

        $nextOrder = (int) $product->variants()->max('sort_order') + 1;

        $product->variants()->create([
            'label' => $data['label'],
            'price' => $data['price'],
            'sort_order' => $nextOrder,
            'is_default' => $data['is_default'] ?? false,
        ]);

        return back();
    }

    public function update(Request $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 403);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_default' => ['boolean'],
        ]);

        if (! empty($data['is_default'])) {
            $product->variants()->where('id', '!=', $variant->id)->update(['is_default' => false]);
        }

        $variant->update($data);

        return back();
    }

    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 403);

        $variant->delete();

        $product->variants()->orderBy('sort_order')->get()
            ->each(fn (ProductVariant $v, int $i) => $v->update(['sort_order' => $i]));

        return back();
    }
}
