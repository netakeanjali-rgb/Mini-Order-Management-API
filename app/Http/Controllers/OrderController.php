<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $orders = auth()->user()
            ->orders()
            ->with('items.product')
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $items = collect($request->validated('items'));
        $productIds = $items->pluck('product_id')->unique()->values();

        try {
            $order = DB::transaction(function () use ($items, $productIds) {
                $products = Product::whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $totalAmount = 0;
                $orderItems = [];

                foreach ($items as $item) {
                    $product = $products->get($item['product_id']);

                    if (! $product) {
                        throw new \RuntimeException('Product not found.');
                    }

                    if ($product->stock < $item['quantity']) {
                        throw new \RuntimeException(
                            "Insufficient stock for product: {$product->name}. Available: {$product->stock}"
                        );
                    }

                    $subtotal = bcmul((string) $product->price, (string) $item['quantity'], 2);
                    $totalAmount = bcadd((string) $totalAmount, $subtotal, 2);

                    $orderItems[] = [
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->price,
                        'subtotal' => $subtotal,
                    ];

                    $product->decrement('stock', $item['quantity']);
                }

                $order = Order::create([
                    'user_id' => auth()->id(),
                    'total_amount' => $totalAmount,
                    'status' => 'completed',
                ]);

                $order->items()->createMany($orderItems);

                return $order->load('items.product');
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Order placed successfully',
            'order' => new OrderResource($order),
        ], 201);
    }

    public function show(Order $order): OrderResource|JsonResponse
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Forbidden. You can only view your own orders.',
            ], 403);
        }

        $order->load('items.product');

        return new OrderResource($order);
    }
}
