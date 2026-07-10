<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Jobs\ProcessOrderJob;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
        $order = Order::create([
            'user_id' => auth()->id(),
            'total_amount' => 0,
            'status' => 'pending',
        ]);

        ProcessOrderJob::dispatch($order, $request->validated('items'));

        return response()->json([
            'message' => 'Order is being processed',
            'order' => new OrderResource($order),
        ], 202);
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
