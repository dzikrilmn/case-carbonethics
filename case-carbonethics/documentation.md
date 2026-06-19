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

This module exposes product catalog endpoints in the API. Public routes allow reading products, while write operations require authentication and an admin role. All responses follow the consistent JSON format with data wrapped in a `data` field.

## Endpoints

### 1. List Products

- Method: GET
- URL: /api/products
- Auth: Not required
- Content-Type: application/json

Success response (200):

```json
{
  "data": [
    {
      "id": 1,
      "name": "Product Name",
      "description": "Product description",
      "price": "29.99",
      "status": "active",
      "created_at": "2026-06-19T10:00:00.000000Z",
      "updated_at": "2026-06-19T10:00:00.000000Z"
    }
  ]
}
```

### 2. Get Product Details

- Method: GET
- URL: /api/products/{id}
- Auth: Not required
- Content-Type: application/json

Success response (200):

```json
{
  "data": {
    "id": 1,
    "name": "Product Name",
    "description": "Product description",
    "price": "29.99",
    "status": "active",
    "created_at": "2026-06-19T10:00:00.000000Z",
    "updated_at": "2026-06-19T10:00:00.000000Z"
  }
}
```

### 3. Create Product

- Method: POST
- URL: /api/products
- Auth: Required (Bearer token)
- Access: Admin only
- Content-Type: application/json

Request body:

```json
{
  "name": "Reusable Bottle",
  "description": "Eco-friendly bottle",
  "price": 29.99,
  "status": "active"
}
```

Success response (201):

```json
{
  "data": {
    "id": 1,
    "name": "Reusable Bottle",
    "description": "Eco-friendly bottle",
    "price": "29.99",
    "status": "active",
    "created_at": "2026-06-19T10:00:00.000000Z",
    "updated_at": "2026-06-19T10:00:00.000000Z"
  }
}
```

### 4. Update Product

- Method: PUT
- URL: /api/products/{id}
- Auth: Required (Bearer token)
- Access: Admin only
- Content-Type: application/json

Request body:

```json
{
  "name": "Updated Product",
  "description": "Updated description",
  "price": 39.99,
  "status": "active"
}
```

Success response (200):

```json
{
  "data": {
    "id": 1,
    "name": "Updated Product",
    "description": "Updated description",
    "price": "39.99",
    "status": "active",
    "created_at": "2026-06-19T10:00:00.000000Z",
    "updated_at": "2026-06-19T10:01:00.000000Z"
  }
}
```

### 5. Delete Product

- Method: DELETE
- URL: /api/products/{id}
- Auth: Required (Bearer token)
- Access: Admin only
- Content-Type: application/json

Success response (200):

```json
{
  "data": {
    "message": "Product deleted successfully."
  }
}
```

## Validation Rules

- `name` is required, must be a string (max 255 characters)
- `price` is required, must be numeric and at least `0`
- `status` is required, must be either `active` or `inactive`
- `description` is optional

Validation error response (422):

```json
{
  "message": "The name field is required. (and 1 more error)",
  "errors": {
    "name": ["The name field is required."],
    "price": ["The price must be at least 0."]
  }
}
```

## Authorization

- **Public endpoints**: GET /api/products and GET /api/products/{id} are accessible without authentication
- **Admin-only endpoints**: POST, PUT, DELETE require:
  - Valid Bearer token in Authorization header
  - User with `admin` role
- **Unauthorized response** (401): missing, invalid, or revoked Bearer token
- **Forbidden response** (403):
```json
{
  "message": "Forbidden."
}
```

## Product Error Responses

All product endpoints use the same JSON error style:

```json
{
  "message": "...",
  "errors": {
    "field": ["error message"]
  }
}
```

### 401 Unauthorized

Used when a protected product action is called without a valid token.

```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden

Used when the user is authenticated but not an admin.

```json
{
  "message": "Forbidden."
}
```

### 404 Not Found

Used when a product ID does not exist.

```json
{
  "message": "Resource not found."
}
```

### 422 Unprocessable Entity

Used when product validation fails.

```json
{
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."],
    "price": ["The price must be at least 0."]
  }
}
```

### 500 Internal Server Error

Used only for unexpected server failures.

```json
{
  "message": "Server error."
}
```

## Implementation Reference

Main product module files:

- app/Http/Controllers/ProductController.php
- app/Http/Resources/Product/ProductResource.php
- app/Http/Resources/Product/ProductCollectionResource.php
- routes/api.php
- app/Models/Product.php
- tests/Feature/ProductModuleTest.php

# Order Module Documentation

This module handles order creation and retrieval. Orders can be created by anyone (public endpoint) and must only contain active products. Order details (price, subtotal, total) are calculated and stored at order creation time. Only admins can view all orders or retrieve specific order details. All success responses use the standard `{ "data": ... }` wrapper and validation errors use `{ "message": "Validation failed", "errors": ... }`.

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
  "data": {
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
}
```

### 2. List Orders

- Method: GET
- URL: /api/orders
- Auth: Required (Bearer token)
- Access: Admin only

Success response (200):

```json
{
  "data": [
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
}
```

### 3. Get Order Details

- Method: GET
- URL: /api/orders/{id}
- Auth: Required (Bearer token)
- Access: Admin only

Success response (200):

```json
{
  "data": {
    "id": 1,
    "customer_name": "Budi",
    "customer_email": "budi@mail.com",
    "status": "pending",
    "total_price": "110.00",
    "created_at": "2026-06-19T10:00:00.000000Z",
    "updated_at": "2026-06-19T10:00:00.000000Z",
    "items": [...]
  }
}
```

## Validation Rules

- `customer_name` is required and must be a string (max 255 characters)
- `customer_email` is required and must be a valid email address
- `items` array is required and must contain at least 1 item
- Each item must have:
  - `product_id`: required, must exist in products table
  - `qty`: required, must be an integer and at least 1

Validation error response (422):

```json
{
  "message": "Validation failed",
  "errors": {
    "customer_name": ["The customer name field is required."],
    "items.0.product_id": ["Product is not active."]
  }
}
```

## Business Rules

- **Only Active Products**: Orders can only be created with products that have status `active`. Attempting to order inactive products will fail with a 422 validation error.
- **Price Snapshot**: When an order is created, the current price of each product is captured and stored in the `order_items.price` column. This ensures price history is maintained even if product prices change later.
- **Subtotal Calculation**: Subtotal for each item = product price × quantity
- **Total Price Calculation**: Total price is the sum of all item subtotals. This is calculated automatically and stored in the `orders.total_price` column.
- **Transaction Safety**: Order creation uses database transactions to ensure data consistency. If any error occurs during order creation, all changes are rolled back.

## Implementation Reference

Main order module files:

- app/Http/Controllers/OrderController.php
- app/Http/Resources/Order/OrderResource.php
- app/Http/Resources/Order/OrderCollectionResource.php
- app/Http/Resources/Order/OrderItemResource.php
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
  "data": {
    "access_token": "1|long-sanctum-token-string",
    "token_type": "Bearer",
    "role": "user"
  }
}
```

Validation error response (422):

```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
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
  "data": {
    "message": "Logged out successfully."
  }
}
```

Error response:

- 401 Unauthorized: token missing, invalid, already revoked, or not found

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

# Error Codes Reference

This section lists all HTTP status codes and error response formats used throughout the API.

## 200 OK

Successful request with data returned.

```json
{
  "data": {
    ...
  }
}
```

Used for: GET, PUT, DELETE operations that succeed.

## 201 Created

Resource successfully created.

```json
{
  "data": {
    "id": 1,
    ...
  }
}
```

Used for: POST operations that create new resources.

## 400 Bad Request

Invalid request format or malformed JSON.

```json
{
  "message": "Invalid request format."
}
```

## 401 Unauthorized

Authentication failed or token invalid/expired.

```json
{
  "message": "Invalid credentials."
}
```

or

```json
{
  "message": "Unauthenticated."
}
```

**Scenarios:**
- POST /api/login with wrong email or password
- Request to protected endpoint without Bearer token
- Request with expired or revoked token
- Request with malformed Bearer token

## 403 Forbidden

Authenticated but user lacks required permissions.

```json
{
  "message": "Forbidden."
}
```

**Scenarios:**
- Non-admin user attempts POST /api/products
- Non-admin user attempts to list or view orders
- User attempts to perform admin-only action

## 404 Not Found

Resource does not exist.

```json
{
  "message": "Resource not found."
}
```

**Scenarios:**
- GET /api/products/999 where product with ID 999 does not exist
- GET /api/orders/999 where order with ID 999 does not exist

## 422 Unprocessable Entity

Validation failed. Request data does not meet requirements.

```json
{
  "message": "Validation failed",
  "errors": {
    "field_name": [
      "The field_name field is required.",
      "The field_name must be at least X."
    ]
  }
}
```

**Common validation scenarios:**
- Missing required fields: `email`, `password`, `name`, `price`, `customer_name`
- Invalid field format: email not valid format, price not numeric
- Field value out of range: `price` less than 0, `qty` less than 1
- Constraint violations: inactive product in order, non-existent product ID
- Array/collection rules: `items` array empty or missing

**Examples:**

Login with missing email:
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

Create order with inactive product:
```json
{
  "message": "Validation failed",
  "errors": {
    "items.0.product_id": ["Product is not active."]
  }
}
```

Create product with invalid price:
```json
{
  "message": "Validation failed",
  "errors": {
    "price": ["The price must be at least 0."]
  }
}
```

## 500 Internal Server Error

Unexpected server error.

```json
{
  "message": "Server error."
}
```

**Scenarios:**
- Database connection failure
- Unexpected exception during request processing
- File system or external service error

## Error Response Format Summary

All error responses follow one of these formats:

**Standard error** (validation, auth, forbidden):
```json
{
  "message": "Error description",
  "errors": {
    "field": ["error details"]
  }
}
```

**Simple error** (auth failure, server error):
```json
{
  "message": "Error description"
}
```

**Success** (all successful requests):
```json
{
  "data": {
    ...
  }
}
```

## Status Code Summary Table

| Code | Meaning | Format | Example Scenario |
|------|---------|--------|-------------------|
| 200 | OK | `{"data": {...}}` | GET products, PUT update product |
| 201 | Created | `{"data": {...}}` | POST login, POST create order |
| 400 | Bad Request | `{"message": "..."}` | Malformed JSON body |
| 401 | Unauthorized | `{"message": "..."}` | Wrong password, missing token |
| 403 | Forbidden | `{"message": "..."}` | Non-admin user tries admin action |
| 404 | Not Found | `{"message": "..."}` | Resource ID does not exist |
| 422 | Unprocessable Entity | `{"message": "...", "errors": {...}}` | Invalid field values |
| 500 | Server Error | `{"message": "..."}` | Database or processing error |

## Troubleshooting: Double Logout Issue

### Problem

When calling POST /api/logout twice with the same token, the second request returned HTTP 500 (Internal Server Error) instead of HTTP 401 (Unauthenticated).

### Root Cause

The `auth:sanctum` middleware would fail to authenticate the revoked token and attempt to redirect to a `login` route, which does not exist in an API context. This caused a RouteNotFoundException that resulted in a 500 error.

### Solution

1. **Removed the `auth:sanctum` middleware** from the logout route in [routes/api.php](routes/api.php)
2. **Moved authentication logic** directly into the logout method in [app/Http/Controllers/AuthController.php](app/Http/Controllers/AuthController.php)
3. **Manual token validation** using `\Auth::guard('sanctum')->user()` to verify the Bearer token
4. **Proper error handling** with try-catch to catch any exceptions and return 401 instead of 500
5. **Updated exception rendering** in [bootstrap/app.php](bootstrap/app.php) to return JSON 401 for AuthenticationException

### Implementation Details

The logout method now:
- Checks for the Authorization header
- Validates the Bearer token format
- Uses the sanctum guard to authenticate the user
- Verifies the token exists before attempting deletion
- Returns clear 401 responses for all error cases
- Catches any exceptions and returns 401 instead of allowing 500 errors

### Expected Behavior After Fix

- **First logout** (valid token): Returns HTTP 200 with message "Logged out successfully."
- **Second logout** (revoked token): Returns HTTP 401 with message "Token already revoked or not found."
- **No valid token**: Returns HTTP 401 with message "Unauthenticated."

This ensures logout is idempotent and safe to call multiple times without causing server errors.

## Troubleshooting: Product Create Returns 500

### Problem

When calling `POST /api/products` without a valid Bearer token, the request returned HTTP 500 instead of HTTP 401.

### Root Cause

Laravel's default auth middleware was trying to redirect guests to a `login` route. Because this API project does not define a web login route, that redirect could throw `Route [login] not defined` and surface as a 500 error.

### Solution

1. **Centralized auth failure rendering** in [bootstrap/app.php](bootstrap/app.php) so any API `AuthenticationException` returns JSON 401
2. **Disabled guest redirects** to avoid Laravel resolving the missing `login` route for API requests
3. **Added a guest product test** in [tests/Feature/ProductModuleTest.php](tests/Feature/ProductModuleTest.php) to lock the expected status code

### Expected Behavior After Fix

- **Guest create product**: Returns HTTP 401 with message "Unauthenticated."
- **Non-admin authenticated user**: Returns HTTP 403 with message "Forbidden."
- **Invalid product input**: Returns HTTP 422 with `message: "Validation failed"` and `errors`

### Troubleshooting Tip

If you still see a 500 on API auth failure, check for any middleware or redirect callback that references `route('login')`. The API renderer in [bootstrap/app.php](bootstrap/app.php) should be the single place that converts auth failures to JSON 401.
