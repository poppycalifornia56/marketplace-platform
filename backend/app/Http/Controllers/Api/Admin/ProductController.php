<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['vendor.user', 'category']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json($products);
    }

    public function show(Product $product)
    {
        return response()->json($product->load(['vendor.user', 'category', 'reviews.user']));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'status' => 'in:draft,published,archived',
            'featured' => 'boolean',
        ]);

        $product->update($request->only(['status', 'featured']));

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
