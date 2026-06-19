# Postman Tutorial for Authentication

This guide explains how to test the Laravel Sanctum authentication feature using Postman.

## Prerequisites

Before testing, make sure:

- The Laravel application is running.
- A test user exists in the database.
- Sanctum authentication is enabled.

Example local base URL:

```text
http://localhost:8000
```

## 1. Create Environment Variables

In Postman, create an environment such as Local API and add these variables:

- base_url = http://localhost:8000
- access_token = (leave empty)

## 2. Test Login Endpoint

### Request

- Method: POST
- URL: {{base_url}}/api/login
- Headers: Content-Type: application/json
- Body type: raw JSON

```json
{
  "email": "test@example.com",
  "password": "password"
}
```

### Tests Tab Script

Use this script to verify the response and save the token automatically:

```javascript
pm.test('Login success', function () {
  pm.response.to.have.status(200);
});

const json = pm.response.json();
pm.environment.set('access_token', json.access_token);
```

### Expected Result

- Response status: 200
- Response includes access_token
- access_token is saved into the Postman environment

## 3. Test Logout Endpoint

### Request

- Method: POST
- URL: {{base_url}}/api/logout
- Headers:
  - Authorization: Bearer {{access_token}}
  - Content-Type: application/json

### Tests Tab Script

```javascript
pm.test('Logout success', function () {
  pm.response.to.have.status(200);
});
```

### Expected Result

- Response status: 200
- Response message: Logged out successfully.
- Current token is deleted from Sanctum

## 4. Verify Token Revocation

After logout, send the same logout request again using the same token.

Expected result:

- Response status: 401 Unauthorized

This confirms the token was revoked correctly.

## Recommended Test Flow

1. Send the login request.
2. Confirm the access_token is stored in the environment.
3. Send the logout request using Authorization: Bearer {{access_token}}.
4. Repeat the logout request to confirm the token is no longer valid.

## Notes

- Always use HTTPS in production.
- Do not share or log access tokens.
- If you want to test another protected endpoint, reuse the same Authorization header format.

# Postman Tutorial for Product Module

This guide explains how to test the product catalog endpoints using Postman.

## Prerequisites

Before testing, make sure:

- The Laravel application is running.
- Database is seeded with test users (run `php artisan db:seed`).
- Test credentials available:
  - **Regular User**: email: `test@example.com`, password: `password`
  - **Admin User**: email: `admin@example.com`, password: `password`

## 1. Test List Products (Public)

### Request

- Method: GET
- URL: {{base_url}}/api/products
- Headers: Content-Type: application/json

### Expected Result

- Response status: 200
- Response contains an array of products with `id`, `name`, `description`, `price`, and `status` fields

## 2. Test Get Product Details (Public)

### Request

- Method: GET
- URL: {{base_url}}/api/products/1
- Headers: Content-Type: application/json

### Expected Result

- Response status: 200
- Response contains a single product object

## 3. Create Product (Admin Only)

**Note**: First, login with the admin account (email: `admin@example.com`, password: `password`) to get an admin token.

### Request

- Method: POST
- URL: {{base_url}}/api/products
- Headers:
  - Authorization: Bearer {{access_token}}
  - Content-Type: application/json
- Body type: raw JSON

```json
{
  "name": "Reusable Bottle",
  "description": "Eco-friendly stainless steel bottle",
  "price": 29.99,
  "status": "active"
}
```

### Tests Tab Script

```javascript
pm.test('Create product success', function () {
  pm.response.to.have.status(201);
});

const json = pm.response.json();
pm.environment.set('product_id', json.id);
```

### Expected Result

- Response status: 201 Created
- Response includes the created product with `id`
- product_id is saved into the Postman environment

## 4. Update Product (Admin Only)

### Request

- Method: PUT
- URL: {{base_url}}/api/products/{{product_id}}
- Headers:
  - Authorization: Bearer {{access_token}}
  - Content-Type: application/json
- Body type: raw JSON

```json
{
  "name": "Premium Reusable Bottle",
  "description": "Updated description",
  "price": 39.99,
  "status": "active"
}
```

### Tests Tab Script

```javascript
pm.test('Update product success', function () {
  pm.response.to.have.status(200);
});
```

### Expected Result

- Response status: 200
- Response contains the updated product

## 5. Delete Product (Admin Only)

### Request

- Method: DELETE
- URL: {{base_url}}/api/products/{{product_id}}
- Headers:
  - Authorization: Bearer {{access_token}}
  - Content-Type: application/json

### Tests Tab Script

```javascript
pm.test('Delete product success', function () {
  pm.response.to.have.status(200);
});
```

### Expected Result

- Response status: 200
- Response message: "Product deleted successfully."

## 6. Test Validation

### Request

- Method: POST
- URL: {{base_url}}/api/products
- Headers:
  - Authorization: Bearer {{access_token}}
  - Content-Type: application/json
- Body type: raw JSON (invalid data)

```json
{
  "price": -5,
  "status": "archived"
}
```

### Expected Result

- Response status: 422 Unprocessable Entity
- Response includes validation errors for `name`, `price`, and `status` fields

## Recommended Test Flow

1. Send login request with regular user (test@example.com) to obtain access_token.
2. Send list products request (public - no auth needed).
3. Send get product details request (public - no auth needed).
4. Send logout request to revoke the regular user token.
5. Login again with admin user (admin@example.com) to get admin token.
6. Create a new product (requires admin token).
7. Update the created product.
8. Delete the product.
9. Test invalid product creation to verify validation.
10. Send logout request to revoke the admin token.

## Notes

- Only admin users can create, update, or delete products.
- Public endpoints (list, show) do not require authentication.
- Prices must be numeric and at least 0.
- Status must be either `active` or `inactive`.
- Product names are required and must not exceed 255 characters.

# Postman Tutorial for Order Module

This guide explains how to test the order management endpoints using Postman.

## Prerequisites

Before testing, make sure:

- The Laravel application is running.
- Database is seeded with test users and products (run `php artisan db:seed`).
- Test products exist with `active` status.
- Test credentials available:
  - **Admin User**: email: `admin@example.com`, password: `password`

## 1. Create Order (Public)

**Note**: This endpoint is public - no authentication required.

### Request

- Method: POST
- URL: {{base_url}}/api/orders
- Headers: Content-Type: application/json
- Body type: raw JSON

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

### Tests Tab Script

```javascript
pm.test('Create order success', function () {
  pm.response.to.have.status(201);
});

const json = pm.response.json();
pm.environment.set('order_id', json.id);
```

### Expected Result

- Response status: 201 Created
- Response includes:
  - Order details: `id`, `customer_name`, `customer_email`, `status` (pending), `total_price`
  - Items array with `product_id`, `qty`, `price` (snapshot), `subtotal`
  - `created_at` and `updated_at` timestamps
- order_id is saved into the Postman environment
- Total price is calculated correctly (sum of all item subtotals)

## 2. List Orders (Admin Only)

**Note**: Login with admin account first to get admin token.

### Request

- Method: GET
- URL: {{base_url}}/api/orders
- Headers:
  - Authorization: Bearer {{access_token}}
  - Content-Type: application/json

### Expected Result

- Response status: 200
- Response is an array of orders, each containing:
  - Order details and items array
  - All orders are included with their items loaded

## 3. Get Order Details (Admin Only)

### Request

- Method: GET
- URL: {{base_url}}/api/orders/{{order_id}}
- Headers:
  - Authorization: Bearer {{access_token}}
  - Content-Type: application/json

### Expected Result

- Response status: 200
- Response contains a single order object with all items included

## 4. Test Validation - Missing Customer Name

### Request

- Method: POST
- URL: {{base_url}}/api/orders
- Headers: Content-Type: application/json
- Body type: raw JSON

```json
{
  "customer_email": "budi@mail.com",
  "items": [
    {
      "product_id": 1,
      "qty": 2
    }
  ]
}
```

### Expected Result

- Response status: 422 Unprocessable Entity
- Response includes validation error for `customer_name`

## 5. Test Validation - Invalid Email

### Request

- Method: POST
- URL: {{base_url}}/api/orders
- Headers: Content-Type: application/json
- Body type: raw JSON

```json
{
  "customer_name": "Budi",
  "customer_email": "not-an-email",
  "items": [
    {
      "product_id": 1,
      "qty": 2
    }
  ]
}
```

### Expected Result

- Response status: 422 Unprocessable Entity
- Response includes validation error for `customer_email`

## 6. Test Validation - No Items

### Request

- Method: POST
- URL: {{base_url}}/api/orders
- Headers: Content-Type: application/json
- Body type: raw JSON

```json
{
  "customer_name": "Budi",
  "customer_email": "budi@mail.com",
  "items": []
}
```

### Expected Result

- Response status: 422 Unprocessable Entity
- Response includes validation error for `items`

## 7. Test Validation - Non-existent Product

### Request

- Method: POST
- URL: {{base_url}}/api/orders
- Headers: Content-Type: application/json
- Body type: raw JSON

```json
{
  "customer_name": "Budi",
  "customer_email": "budi@mail.com",
  "items": [
    {
      "product_id": 999,
      "qty": 2
    }
  ]
}
```

### Expected Result

- Response status: 422 Unprocessable Entity
- Response includes validation error indicating product not found

## 8. Test Validation - Inactive Product

**Note**: First, update a product to have status `inactive` using the Product Module endpoints.

### Request

- Method: POST
- URL: {{base_url}}/api/orders
- Headers: Content-Type: application/json
- Body type: raw JSON

```json
{
  "customer_name": "Budi",
  "customer_email": "budi@mail.com",
  "items": [
    {
      "product_id": 3,
      "qty": 1
    }
  ]
}
```

### Expected Result

- Response status: 422 Unprocessable Entity
- Response includes validation error: "Product is not active"

## 9. Test Authorization - Non-admin Cannot List Orders

### Request

- Method: GET
- URL: {{base_url}}/api/orders
- Headers:
  - Authorization: Bearer {{access_token}} (regular user token)
  - Content-Type: application/json

### Expected Result

- Response status: 403 Forbidden
- Response message: "Forbidden."

## 10. Test Authorization - Non-admin Cannot View Order Details

### Request

- Method: GET
- URL: {{base_url}}/api/orders/{{order_id}}
- Headers:
  - Authorization: Bearer {{access_token}} (regular user token)
  - Content-Type: application/json

### Expected Result

- Response status: 403 Forbidden
- Response message: "Forbidden."

## Recommended Test Flow

1. Create a product with `active` status using Product Module (note the product_id).
2. Send create order request (public - no auth needed) with the active product.
3. Confirm order is created with correct `total_price` and item prices are snapshots.
4. Login with admin user to get admin token.
5. Send list orders request to view all orders.
6. Send get order details request using the order_id from step 2.
7. Test various validation scenarios (missing fields, invalid email, inactive product, non-existent product).
8. Test authorization by trying to list/view orders with regular user token (should fail with 403).
9. Verify price snapshots are independent - create another order, then change the product price, verify the new order has the updated price while the old order retains the original snapshot.

## Key Features to Verify

- **Price Snapshot**: Each order item stores the product price at order creation time. Verify this by:
  1. Note the price from a product (e.g., $50.00)
  2. Create an order with that product
  3. Update the product price (e.g., to $60.00)
  4. Create another order with the same product
  5. Both orders should show their respective prices in the items (first $50.00, second $60.00)

- **Automatic Calculations**: Verify subtotals and total prices are calculated correctly:
  - Subtotal = qty × price
  - Total price = sum of all subtotals

- **Only Active Products**: Ensure orders cannot be created with inactive products.

- **Admin Only Access**: Ensure only admin users can list and view orders.

## Notes

- Orders can be created by anyone without authentication (public endpoint).
- Only admin users can list orders or view order details.
- All products in an order must have status `active` at order creation time.
- Product prices are captured as snapshots; changing product price does not affect existing orders.
- Subtotal and total_price are calculated automatically - do not send them in the request body.
- Order status defaults to `pending` on creation.
