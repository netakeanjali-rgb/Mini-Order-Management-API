<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/orders',
    operationId: 'listOrders',
    tags: ['Orders'],
    summary: 'Get logged-in user orders',
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'List of user orders'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Post(
    path: '/api/orders',
    operationId: 'createOrder',
    tags: ['Orders'],
    summary: 'Place an order (queued processing)',
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['items'],
            properties: [
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    items: new OA\Items(
                        required: ['product_id', 'quantity'],
                        properties: [
                            new OA\Property(property: 'product_id', type: 'integer', example: 1),
                            new OA\Property(property: 'quantity', type: 'integer', example: 2),
                        ]
                    ),
                    example: [['product_id' => 1, 'quantity' => 2], ['product_id' => 2, 'quantity' => 1]]
                ),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 202, description: 'Order is being processed'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 422, description: 'Validation or stock error'),
    ]
)]
#[OA\Get(
    path: '/api/orders/{id}',
    operationId: 'showOrder',
    tags: ['Orders'],
    summary: 'Get order details',
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Order details'),
        new OA\Response(response: 403, description: 'Not your order'),
        new OA\Response(response: 404, description: 'Order not found'),
    ]
)]
class OrderDocumentation
{
}
