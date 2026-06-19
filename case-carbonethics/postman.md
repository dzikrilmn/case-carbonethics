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
  "email": "user@example.com",
  "password": "your-password"
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
