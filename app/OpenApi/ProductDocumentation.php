<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/products',
    operationId: 'listProducts',
    tags: ['Products'],
    summary: 'List products with optional filters',
    parameters: [
        new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Search by product name'),
        new OA\Parameter(name: 'min_price', in: 'query', schema: new OA\Schema(type: 'number')),
        new OA\Parameter(name: 'max_price', in: 'query', schema: new OA\Schema(type: 'number')),
        new OA\Parameter(name: 'min_stock', in: 'query', schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['name', 'price', 'stock', 'created_at'])),
        new OA\Parameter(name: 'sort_order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
    ],
    responses: [
        new OA\Response(response: 200, description: 'List of products'),
    ]
)]
#[OA\Get(
    path: '/api/products/{id}',
    operationId: 'showProduct',
    tags: ['Products'],
    summary: 'Get single product',
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Product details'),
        new OA\Response(response: 404, description: 'Product not found'),
    ]
)]
#[OA\Post(
    path: '/api/products',
    operationId: 'createProduct',
    tags: ['Products'],
    summary: 'Create product (admin only)',
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'price', 'stock'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Wireless Mouse'),
                new OA\Property(property: 'description', type: 'string', example: 'Ergonomic mouse'),
                new OA\Property(property: 'price', type: 'number', format: 'float', example: 29.99),
                new OA\Property(property: 'stock', type: 'integer', example: 50),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Product created'),
        new OA\Response(response: 403, description: 'Admin access required'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Put(
    path: '/api/products/{id}',
    operationId: 'updateProduct',
    tags: ['Products'],
    summary: 'Update product (admin only)',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'price', type: 'number', format: 'float'),
                new OA\Property(property: 'stock', type: 'integer'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Product updated'),
        new OA\Response(response: 403, description: 'Admin access required'),
        new OA\Response(response: 404, description: 'Product not found'),
    ]
)]
#[OA\Delete(
    path: '/api/products/{id}',
    operationId: 'deleteProduct',
    tags: ['Products'],
    summary: 'Delete product (admin only)',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Product deleted'),
        new OA\Response(response: 403, description: 'Admin access required'),
        new OA\Response(response: 422, description: 'Product has orders'),
    ]
)]
class ProductDocumentation
{
}
