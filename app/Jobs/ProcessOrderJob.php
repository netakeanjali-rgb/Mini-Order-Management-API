<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProcessOrderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public array $items,
    ) {}

    public function handle(): void
    {
        $items = collect($this->items);
        $productIds = $items->pluck('product_id')->unique()->values();

        try {
            DB::transaction(function () use ($items, $productIds) {
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
                    Cache::forget("products.show.{$product->id}");
                }

                $this->order->update([
                    'total_amount' => $totalAmount,
                    'status' => 'completed',
                ]);

                $this->order->items()->createMany($orderItems);

                Cache::increment('products.cache_version');
            });

            SendOrderEmailJob::dispatch($this->order);
        } catch (\RuntimeException $e) {
            $this->order->update(['status' => 'failed']);
        }
    }
}
