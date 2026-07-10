<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Mini Order Management API',
    description: 'REST API for product management and order placement with JWT authentication.'
)]
#[OA\Server(url: 'http://127.0.0.1:8000', description: 'Local development server')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Enter JWT token from login/register response'
)]
#[OA\Tag(name: 'Authentication', description: 'User registration and login')]
#[OA\Tag(name: 'Products', description: 'Product catalog')]
#[OA\Tag(name: 'Orders', description: 'Order management')]
class OpenApiSpec
{
}
