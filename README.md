# Mini Order Management API

A Laravel REST API for managing products and placing orders. Built as a take-home backend assignment covering authentication, CRUD, order processing, and R&D features.

## Tech Stack

- Laravel 13
- PHP 8.3+
- MySQL
- JWT Authentication (`php-open-source-saver/jwt-auth`)
- Redis (caching)
- Database queues

## Setup

### 1. Clone and install dependencies

```bash
git clone https://github.com/netakeanjali-rgb/Mini-Order-Management-API.git
cd Mini-Order-Management-API
composer install
```

### 2. Environment configuration

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Update `.env` with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mini_order_db
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Run migrations and seeders

```bash
php artisan migrate --seed
```

### 4. Start the application

```bash
php artisan serve
```

### 5. Start the queue worker (required for order processing)

```bash
php artisan queue:work
```

### 6. Redis (optional but recommended for caching)

Install and start Redis, then set in `.env`:

```env
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

If Redis is not available, use `CACHE_STORE=database` as a fallback.

## Docker Setup (Optional)

Run the full stack with Docker:

```bash
docker compose up --build
```

This starts:
- **app** — Laravel API at http://localhost:8000
- **queue** — background order processing worker
- **mysql** — database (host port `3308` to avoid XAMPP conflicts)
- **redis** — cache store

Seed sample data after containers are up:

```bash
docker compose exec app php artisan db:seed
```

Run tests inside Docker:

```bash
docker compose exec app php artisan test
```

Stop containers:

```bash
docker compose down
```

Docker environment defaults:
- MySQL password: `secret`
- Database: `mini_order_db`
- Redis host: `redis`

## Default Users

| Email | Password | Role |
|-------|----------|------|
| `test@example.com` | `password` | user |
| `admin@example.com` | `password` | admin |

## API Endpoints

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/register` | No | Register a new user |
| POST | `/api/login` | No | Login and receive JWT token |
| POST | `/api/logout` | Yes | Invalidate JWT token |

### Products

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/products` | No | List products (supports search filters) |
| GET | `/api/products/{id}` | No | Get single product |
| POST | `/api/products` | Admin | Create product |
| PUT | `/api/products/{id}` | Admin | Update product |
| DELETE | `/api/products/{id}` | Admin | Delete product |

**Product search filters** (query params on `GET /api/products`):

| Parameter | Description |
|-----------|-------------|
| `search` | Filter by product name |
| `min_price` | Minimum price |
| `max_price` | Maximum price |
| `min_stock` | Minimum stock |
| `sort_by` | Sort field: `name`, `price`, `stock`, `created_at` |
| `sort_order` | `asc` or `desc` |

Example:

```
GET /api/products?search=mouse&min_price=10&max_price=50&sort_by=price&sort_order=asc
```

### Orders

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/orders` | Yes | Place an order (queued processing) |
| GET | `/api/orders` | Yes | List logged-in user's orders |
| GET | `/api/orders/{id}` | Yes | Get order details |

**Create order example:**

```json
{
  "items": [
    {"product_id": 1, "quantity": 2},
    {"product_id": 2, "quantity": 1}
  ]
}
```

## R&D Features

### 1. Redis Caching for Products

Product list and single product responses are cached for 1 hour using Laravel's `Cache` facade. A cache version key is incremented whenever a product is created, updated, or deleted, which invalidates all list caches. Individual product caches are cleared on update/delete.

**Why:** Reduces database queries for frequently accessed product data.

**Config:** Set `CACHE_STORE=redis` in `.env`.

### 2. API Rate Limiting

All API routes are limited to **60 requests per minute** per user (or per IP for unauthenticated requests).

**Implementation:** Laravel's `throttle:api` middleware with a custom rate limiter in `AppServiceProvider`.

### 3. Queue for Order Processing

When a user places an order, the API immediately creates a `pending` order and dispatches a `ProcessOrderJob` to the queue. The job handles:

- Stock validation with row locking
- Stock reduction
- Total price calculation
- Order item creation
- Status update to `completed` or `failed`

After the order is completed, a separate `SendOrderEmailJob` is dispatched so email failures only retry the email — not the order processing.

**Why:** Keeps the API response fast and moves heavy work to a background worker.

**Requires:** `php artisan queue:work` running with `QUEUE_CONNECTION=database`.

### 4. Email Notification After Order

After an order is successfully processed, `SendOrderEmailJob` sends an `OrderPlaced` mailable to the user with order details.

**Dev setup:** `MAIL_MAILER=log` writes emails to `storage/logs/laravel.log`.

### 5. Product Search Filters

The product list endpoint supports filtering by name, price range, stock, and custom sorting via query parameters (see API Endpoints above).

## Authentication Usage

Include the JWT token in the `Authorization` header:

```
Authorization: Bearer YOUR_TOKEN_HERE
```

## Swagger / OpenAPI Documentation

Laravel does not include Swagger by default. This project uses the **[L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger)** package.

**View docs UI:** [http://127.0.0.1:8000/api/documentation](http://127.0.0.1:8000/api/documentation)

**Regenerate docs** after changing API annotations:

```bash
php artisan l5-swagger:generate
```

To test protected endpoints in Swagger UI:
1. Call `POST /api/login` and copy the `token` from the response
2. Click **Authorize** at the top of Swagger UI
3. Enter: `Bearer YOUR_TOKEN_HERE`

Default admin login for testing: `admin@example.com` / `password`

## Running Tests

PHPUnit feature tests use an in-memory SQLite database and run queued jobs synchronously.

```bash
php artisan test
```

## Project Structure

```
app/
├── Http/
│   ├── Controllers/     # Auth, Product, Order controllers
│   ├── Middleware/       # Admin authorization
│   ├── Requests/         # Form request validation
│   └── Resources/        # API JSON transformers
├── Jobs/                 # ProcessOrderJob, SendOrderEmailJob (queued)
├── Mail/                 # OrderPlaced notification
└── Models/               # User, Product, Order, OrderItem
```

## License

MIT
