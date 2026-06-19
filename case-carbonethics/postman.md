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
