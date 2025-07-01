<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with(['items.product', 'items.vendor'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($orders);
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['items.product', 'items.vendor']);

        return response()->json($order);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'billing_address' => 'required|array',
            'shipping_address' => 'required|array',
            'payment_method' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $subtotal = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                if (!$product->isInStock()) {
                    return response()->json([
                        'message' => "Product {$product->name} is out of stock"
                    ], 400);
                }

                if ($product->manage_stock && $product->quantity < $item['quantity']) {
                    return response()->json([
                        'message' => "Insufficient stock for {$product->name}"
                    ], 400);
                }

                $price = $product->getCurrentPrice();
                $total = $price * $item['quantity'];
                $subtotal += $total;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'vendor_id' => $product->vendor_id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'price' => $price,
                    'quantity' => $item['quantity'],
                    'total' => $total,
                ];

                // Update stock
                if ($product->manage_stock) {
                    $product->decrement('quantity', $item['quantity']);
                }
            }

            $taxAmount = $subtotal * 0.1; // 10% tax
            $shippingAmount = 10.00; // Fixed shipping
            $totalAmount = $subtotal + $taxAmount + $shippingAmount;

            $order = Order::create([
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'total_amount' => $totalAmount,
                'billing_address' => $request->billing_address,
                'shipping_address' => $request->shipping_address,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            return response()->json($order->load('items'), 201);
        });
    }
}
