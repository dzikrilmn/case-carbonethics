# Project Overview

This repository is a Laravel 12 application that provides an API-first backend with token-based authentication using Laravel Sanctum. The codebase follows standard Laravel conventions and is organized into these primary areas:

- `app/`: application logic, controllers, and models (e.g. `app/Http/Controllers/`, `app/Models/`).
- `routes/`: route definitions for API and web endpoints (notably `routes/api.php`).
- `config/`: framework and package configuration files.
- `database/`: migrations, factories, and seeders that describe the schema and test data.
- `resources/`: front-end assets and Blade views.
- `public/`: web entry point and publicly served files.
- `tests/`: automated tests for Feature and Unit testing.
- `vendor/`: third-party dependencies managed by Composer.

Authentication functionality is implemented using Laravel Sanctum personal access tokens. Key authentication-related files are located at `app/Http/Controllers/AuthController.php`, `routes/api.php`, and `app/Models/User.php`. The API exposes login and logout endpoints which issue and revoke personal access tokens respectively.

Use this document to locate authentication endpoints, product module endpoints, testing instructions, implementation references, and security notes. Open the referenced files for implementation details and follow the migration and seeding files in `database/` when setting up a local environment.

# Database Structure

This repository now includes a simple catalog and order schema in addition to the existing user/authentication setup.

## Users

- `id`
- `name`
- `email`
- `password`
- `role` (`admin` | `user`)

## Products

- `id`
- `name`
- `description` nullable
- `price` decimal
- `status` (`active` | `inactive`)

## Orders

- `id`
- `customer_name`
- `customer_email`
- `status` (`pending` | `paid` | `cancelled`)
- `total_price` decimal

## Order Items

- `id`
- `order_id`
- `product_id`
- `qty`
- `price` snapshot value
- `subtotal`

## Implementation Reference

Relevant files for the database layer:

- database/migrations/2026_06_19_000003_add_role_to_users_table.php
- database/migrations/2026_06_19_140000_create_products_table.php
- database/migrations/2026_06_19_140100_create_orders_table.php
- database/migrations/2026_06_19_140200_create_order_items_table.php
- app/Models/User.php
- app/Models/Product.php
- app/Models/Order.php
- app/Models/OrderItem.php

# Product Module Documentation

This module exposes product catalog endpoints in the API. Public routes allow reading products, while write operations require authentication and an admin role.

## Endpoints

### 1. List Products

- Method: GET
- URL: /api/products
- Auth: Not required

### 2. Get Product Details

- Method: GET
- URL: /api/products/{id}
- Auth: Not required

### 3. Create Product

- Method: POST
- URL: /api/products
- Auth: Required (Bearer token)
- Access: Admin only

### 4. Update Product

- Method: PUT
- URL: /api/products/{id}
- Auth: Required (Bearer token)
- Access: Admin only

### 5. Delete Product

- Method: DELETE
- URL: /api/products/{id}
- Auth: Required (Bearer token)
- Access: Admin only

## Validation Rules

- `name` is required
- `price` must be numeric and at least `0`
- `status` must be either `active` or `inactive`

## Implementation Reference

Main product module files:

- app/Http/Controllers/ProductController.php
- routes/api.php
- app/Models/Product.php
- tests/Feature/ProductModuleTest.php

# Order Module Documentation

This module handles order creation and retrieval. Orders can be created by anyone (public endpoint) and must only contain active products. Order details (price, subtotal, total) are calculated and stored at order creation time. Only admins can view all orders or retrieve specific order details.

## Endpoints

### 1. Create Order

- Method: POST
- URL: /api/orders
- Auth: Not required
- Content-Type: application/json

Request body:

```json
{
  "customer_name": "Budi",
  "customer_email": "budi@mail.com",
  "items": [
    {
      "product_id": 1,
      "qty": 2
    },
    {
      "product_id": 2,
      "qty": 1
    }
  ]
}
```

Success response (201):

```json
{
  "id": 1,
  "customer_name": "Budi",
  "customer_email": "budi@mail.com",
  "status": "pending",
  "total_price": "110.00",
  "created_at": "2026-06-19T10:00:00.000000Z",
  "updated_at": "2026-06-19T10:00:00.000000Z",
  "items": [
    {
      "id": 1,
      "order_id": 1,
      "product_id": 1,
      "qty": 2,
      "price": "50.00",
      "subtotal": "100.00",
      "created_at": "2026-06-19T10:00:00.000000Z",
      "updated_at": "2026-06-19T10:00:00.000000Z"
    },
    {
      "id": 2,
      "order_id": 1,
      "product_id": 2,
      "qty": 1,
      "price": "10.00",
      "subtotal": "10.00",
      "created_at": "2026-06-19T10:00:00.000000Z",
      "updated_at": "2026-06-19T10:00:00.000000Z"
    }
  ]
}
```

### 2. List Orders

- Method: GET
- URL: /api/orders
- Auth: Required (Bearer token)
- Access: Admin only

Success response (200):

```json
[
  {
    "id": 1,
    "customer_name": "Budi",
    "customer_email": "budi@mail.com",
    "status": "pending",
    "total_price": "110.00",
    "created_at": "2026-06-19T10:00:00.000000Z",
    "updated_at": "2026-06-19T10:00:00.000000Z",
    "items": [...]
  }
]
```

### 3. Get Order Details

- Method: GET
- URL: /api/orders/{id}
- Auth: Required (Bearer token)
- Access: Admin only

Success response (200):

```json
{
  "id": 1,
  "customer_name": "Budi",
  "customer_email": "budi@mail.com",
  "status": "pending",
  "total_price": "110.00",
  "created_at": "2026-06-19T10:00:00.000000Z",
  "updated_at": "2026-06-19T10:00:00.000000Z",
  "items": [...]
}
```

## Validation Rules

- `customer_name` is required and must be a string (max 255 characters)
- `customer_email` is required and must be a valid email address
- `items` array is required and must contain at least 1 item
- Each item must have:
  - `product_id`: required, must exist in products table
  - `qty`: required, must be an integer and at least 1

## Business Rules

- **Only Active Products**: Orders can only be created with products that have status `active`. Attempting to order inactive products will fail with a 422 validation error.
- **Price Snapshot**: When an order is created, the current price of each product is captured and stored in the `order_items.price` column. This ensures price history is maintained even if product prices change later.
- **Subtotal Calculation**: Subtotal for each item = product price × quantity
- **Total Price Calculation**: Total price is the sum of all item subtotals. This is calculated automatically and stored in the `orders.total_price` column.
- **Transaction Safety**: Order creation uses database transactions to ensure data consistency. If any error occurs during order creation, all changes are rolled back.

## Implementation Reference

Main order module files:

- app/Http/Controllers/OrderController.php
- routes/api.php
- app/Models/Order.php
- app/Models/OrderItem.php
- tests/Feature/OrderModuleTest.php

# Authentication Feature Documentation

This project uses Laravel Sanctum for API token authentication.

## Overview

Implemented authentication endpoints:

- POST /api/login
- POST /api/logout

Login returns a Sanctum personal access token.
Logout deletes the currently active token used by the request.

## Role System

The `users` table stores a `role` column with two valid values:

- `admin`
- `user`

New users default to `user` unless explicitly assigned `admin`.

## Tech Stack

- Laravel 12
- Laravel Sanctum 4

## Endpoints

### 1. Login

- Method: POST
- URL: /api/login
- Auth: Not required
- Content-Type: application/json

Request body:

```json
{
  "email": "user@example.com",
  "password": "your-password"
}
```

Success response (200):

```json
{
  "access_token": "1|long-sanctum-token-string",
  "token_type": "Bearer",
  "role": "user"
}
```

### 2. Logout

- Method: POST
- URL: /api/logout
- Auth: Required (Bearer token)
- Content-Type: application/json

Required header:

```http
Authorization: Bearer <access_token>
```

Success response (200):

```json
{
  "message": "Logged out successfully."
}
```

Error response:

- 401 Unauthorized: token missing, invalid, or already revoked

For Postman testing instructions, see [postman.md](postman.md).

## Implementation Reference

Main implementation files:

- app/Http/Controllers/AuthController.php
- routes/api.php
- app/Models/User.php

## Security Notes

- Always use HTTPS in production.
- Do not log or expose access tokens.
- Store tokens securely on client side.
- On logout, only the current token is revoked.
- To revoke all tokens for a user, use Laravel Sanctum token deletion on all user tokens.
