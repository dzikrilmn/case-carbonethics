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

Use this document to locate authentication endpoints, testing instructions, implementation references, and security notes. Open the referenced files for implementation details and follow the migration and seeding files in `database/` when setting up a local environment.

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
