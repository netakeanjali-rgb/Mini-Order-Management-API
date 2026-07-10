<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/api/register',
    operationId: 'register',
    tags: ['Authentication'],
    summary: 'Register a new user',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'email', 'password'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'User registered successfully'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Post(
    path: '/api/login',
    operationId: 'login',
    tags: ['Authentication'],
    summary: 'Login user',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'test@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Login successful'),
        new OA\Response(response: 401, description: 'Invalid credentials'),
    ]
)]
#[OA\Post(
    path: '/api/logout',
    operationId: 'logout',
    tags: ['Authentication'],
    summary: 'Logout user',
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Logged out successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
class AuthDocumentation
{
}
