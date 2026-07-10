<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListProductsRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index(ListProductsRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $cacheKey = $this->productListCacheKey($filters);

        $products = Cache::remember($cacheKey, 3600, function () use ($filters) {
            $query = Product::query();

            if (! empty($filters['search'])) {
                $query->where('name', 'like', '%'.$filters['search'].'%');
            }

            if (isset($filters['min_price'])) {
                $query->where('price', '>=', $filters['min_price']);
            }

            if (isset($filters['max_price'])) {
                $query->where('price', '<=', $filters['max_price']);
            }

            if (isset($filters['min_stock'])) {
                $query->where('stock', '>=', $filters['min_stock']);
            }

            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';

            return $query->orderBy($sortBy, $sortOrder)->get();
        });

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());
        $this->clearProductCache();

        return response()->json([
            'message' => 'Product created successfully',
            'product' => new ProductResource($product),
        ], 201);
    }

    public function show(Product $product): ProductResource
    {
        $product = Cache::remember("products.show.{$product->id}", 3600, fn () => $product);

        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());
        $this->clearProductCache($product->id);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => new ProductResource($product),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->orderItems()->exists()) {
            return response()->json([
                'message' => 'Cannot delete product that has been ordered',
            ], 422);
        }

        $productId = $product->id;
        $product->delete();
        $this->clearProductCache($productId);

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    private function productListCacheKey(array $filters): string
    {
        $version = Cache::get('products.cache_version', 1);

        return 'products.list.v'.$version.'.'.md5(json_encode($filters));
    }

    private function clearProductCache(?int $productId = null): void
    {
        Cache::increment('products.cache_version');

        if ($productId) {
            Cache::forget("products.show.{$productId}");
        }
    }
}
