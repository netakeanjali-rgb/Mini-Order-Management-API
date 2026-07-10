<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderTest extends TestCase
{
    public function test_user_can_place_order_and_stock_is_reduced(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 50.00, 'stock' => 10]);

        $response = $this->actingAsApiUser($user)->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertAccepted()
            ->assertJsonPath('order.status', 'pending');

        $orderId = $response->json('order.id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'completed',
            'total_amount' => 100.00,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 8,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'subtotal' => 100.00,
        ]);
    }

    public function test_order_fails_when_stock_is_insufficient(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 1]);

        $response = $this->actingAsApiUser($user)->postJson('/api/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ]);

        $response->assertAccepted()
            ->assertJsonPath('order.status', 'pending');

        $orderId = $response->json('order.id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 1,
        ]);
    }

    public function test_user_can_list_their_orders(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'completed']);
        Order::factory()->create(['status' => 'completed']);

        $response = $this->actingAsApiUser($user)->getJson('/api/orders');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_view_their_order_details(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 99.99,
            'status' => 'completed',
        ]);

        $response = $this->actingAsApiUser($user)->getJson('/api/orders/'.$order->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.total_amount', '99.99');
    }

    public function test_user_cannot_view_another_users_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAsApiUser($user)
            ->getJson('/api/orders/'.$order->id)
            ->assertForbidden();
    }
}
