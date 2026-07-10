<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_guest_can_list_products(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_guest_can_view_single_product(): void
    {
        $product = Product::factory()->create(['name' => 'Wireless Mouse']);

        $response = $this->getJson('/api/products/'.$product->id);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Wireless Mouse');
    }

    public function test_products_can_be_filtered_by_search(): void
    {
        Product::factory()->create(['name' => 'Wireless Mouse', 'price' => 29.99]);
        Product::factory()->create(['name' => 'Mechanical Keyboard', 'price' => 89.99]);

        $response = $this->getJson('/api/products?search=mouse');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Wireless Mouse');
    }

    public function test_regular_user_cannot_create_product(): void
    {
        $response = $this->actingAsApiUser()->postJson('/api/products', [
            'name' => 'Blocked Product',
            'description' => 'Should not be created',
            'price' => 19.99,
            'stock' => 5,
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_create_product(): void
    {
        $response = $this->actingAsAdmin()->postJson('/api/products', [
            'name' => 'USB Hub',
            'description' => '7-in-1 hub',
            'price' => 45.00,
            'stock' => 10,
        ]);

        $response->assertCreated()
            ->assertJsonPath('product.name', 'USB Hub');

        $this->assertDatabaseHas('products', ['name' => 'USB Hub']);
    }

    public function test_admin_can_update_product(): void
    {
        $product = Product::factory()->create(['price' => 29.99]);

        $response = $this->actingAsAdmin()->putJson('/api/products/'.$product->id, [
            'price' => 34.99,
        ]);

        $response->assertOk()
            ->assertJsonPath('product.price', '34.99');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => 34.99,
        ]);
    }

    public function test_admin_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAsAdmin()->deleteJson('/api/products/'.$product->id);

        $response->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
