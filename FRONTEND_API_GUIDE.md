# Multi-Vendor Marketplace — Frontend API Integration Guide

Welcome to the **Multi-Vendor Marketplace API**. This guide covers everything frontend developers (React, Vue, Next.js, Mobile) need to know to integrate with the backend API seamlessly.

---

## 1. General API Conventions

### Base URL
```text
http://localhost:8000/api
```
*(Or your configured domain/port)*

### Standard Request Headers
Always send these headers with every request:
```http
Accept: application/json
Content-Type: application/json
```

### Authenticated Request Headers
For protected endpoints, include the JWT bearer token received during login or registration:
```http
Authorization: Bearer <your_jwt_access_token>
```

---

## 2. Standard Error Response Format

All validation and domain errors follow a consistent JSON structure:

```json
{
  "message": "Human-readable explanation of what went wrong.",
  "code": "MACHINE_READABLE_ERROR_CODE",
  "details": {}
}
```

### Common Error Codes

| HTTP Status | Error `code` | Meaning / How Frontend Should Handle |
|---|---|---|
| `401 Unauthorized` | `UNAUTHORIZED` | Token is missing or expired. Redirect user to `/login`. |
| `403 Forbidden` | `VENDOR_NOT_APPROVED` | Vendor tried to create/edit products before admin approval. Show banner: *"Your shop is pending approval"*. |
| `403 Forbidden` | `NOT_VENDOR` | Customer tried to access vendor-only routes. |
| `403 Forbidden` | `ROLE_FORBIDDEN` | User does not have the required role (e.g. non-admin accessing admin portal). |
| `422 Unprocessable` | `INSUFFICIENT_STOCK` | Stock check failed during checkout. Details specify which product and available stock. |
| `402 Payment Req.` | `PAYMENT_FAILED` | Payment gateway declined the transaction. Prompt user for another card. |

---

## 3. User Roles & Permissions

The system supports 3 roles:
- **`customer`**: Can browse products, checkout, and view their order history.
- **`vendor`**: Can manage shop profile, view their earnings/orders, and manage products *(once approved)*.
- **`admin`**: Can approve/reject vendors and view platform commission analytics.

---

## 4. Endpoints Specification

### A. Authentication (`/auth`)

#### 1. Register User / Vendor
- **Method**: `POST`
- **URL**: `/auth/register`
- **Auth**: Public

**Request Body (Customer):**
```json
{
  "username": "alex99",
  "email": "alex@example.com",
  "password": "Password123!",
  "role": "customer"
}
```

**Request Body (Vendor):**
```json
{
  "username": "gadgethub",
  "email": "vendor@gadgethub.com",
  "password": "Password123!",
  "role": "vendor",
  "shop_name": "Gadget Hub Official",
  "shop_description": "Top quality electronics and accessories."
}
```
> **Note for Frontend:** When `role: "vendor"` is sent, the backend automatically provisions their vendor shop profile in an `is_approved: false` state.

**Response (201 Created):**
```json
{
  "message": "User registered successfully.",
  "user": {
    "id": 1,
    "username": "gadgethub",
    "email": "vendor@gadgethub.com",
    "role": "vendor",
    "is_active": true,
    "vendor": {
      "id": 1,
      "shop_name": "Gadget Hub Official",
      "shop_description": "Top quality electronics and accessories.",
      "is_approved": false
    }
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGci...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

---

#### 2. Login
- **Method**: `POST`
- **URL**: `/auth/login`
- **Auth**: Public

**Request Body:**
```json
{
  "email": "alex@example.com",
  "password": "Password123!"
}
```

**Response (200 OK):**
```json
{
  "message": "Login successful.",
  "user": {
    "id": 1,
    "username": "alex99",
    "email": "alex@example.com",
    "role": "customer",
    "is_active": true,
    "vendor": null
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGci...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

---

#### 3. Current User Profile
- **Method**: `GET`
- **URL**: `/auth/me`
- **Auth**: `Bearer Token`

**Response (200 OK):**
```json
{
  "user": {
    "id": 1,
    "username": "gadgethub",
    "email": "vendor@gadgethub.com",
    "role": "vendor",
    "is_active": true,
    "vendor": {
      "id": 1,
      "shop_name": "Gadget Hub Official",
      "is_approved": true
    }
  }
}
```

---

#### 4. Logout / Refresh Token
- **Logout**: `POST /auth/logout` (Invalidates token)
- **Refresh**: `POST /auth/refresh` (Returns fresh token)

---

### B. Public Product Catalog (`/products`)

#### 1. List Products (Paginated)
- **Method**: `GET`
- **URL**: `/products`
- **Auth**: Public
- **Query Parameters**:
  - `page`: Page number (default: `1`)
  - `per_page`: Items per page (default: `15`)
  - `search`: Filter by name keyword (e.g. `?search=headset`)
  - `vendor_id`: Filter by vendor ID (e.g. `?vendor_id=3`)

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 12,
      "vendor_id": 2,
      "name": "Wireless Noise-Cancelling Headphones",
      "slug": "wireless-noise-cancelling-headphones",
      "description": "Premium sound with 40-hour battery life.",
      "image": "https://example.com/images/headphones.jpg",
      "price": 149.99,
      "stock_quantity": 25,
      "total_sold": 10,
      "is_active": true,
      "flat_discount": null,
      "percentage_discount": 10,
      "vendor": {
        "id": 2,
        "shop_name": "SoundWave Studio"
      }
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/products?page=1",
    "last": "http://localhost:8000/api/products?page=4",
    "prev": null,
    "next": "http://localhost:8000/api/products?page=2"
  },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 52
  }
}
```

---

#### 2. Get Single Product by Slug
- **Method**: `GET`
- **URL**: `/products/{slug}` (e.g. `/products/wireless-noise-cancelling-headphones`)
- **Auth**: Public

**Response (200 OK):**
```json
{
  "data": {
    "id": 12,
    "name": "Wireless Noise-Cancelling Headphones",
    "slug": "wireless-noise-cancelling-headphones",
    "price": 149.99,
    "stock_quantity": 25,
    "vendor": {
      "id": 2,
      "shop_name": "SoundWave Studio"
    }
  }
}
```

---

### C. Customer Checkout & Orders

#### 1. Place Order / Checkout
- **Method**: `POST`
- **URL**: `/checkout`
- **Auth**: `Bearer Token` (`customer` or `admin`)

**Request Body:**
```json
{
  "items": [
    {
      "product_id": 12,
      "quantity": 2
    },
    {
      "product_id": 15,
      "quantity": 1
    }
  ],
  "payment_method": "stripe",
  "shipping_amount": 10.00
}
```
*Supported `payment_method`: `"stripe"`, `"cod"` (Cash On Delivery).*

**Success Response (201 Created):**
```json
{
  "message": "Order placed successfully.",
  "order": {
    "id": 101,
    "order_number": "ORD-E7D3F9B2C1",
    "total_amount": 349.98,
    "status": "processing",
    "payment_method": "stripe",
    "payment_status": "paid",
    "shipping_amount": 10.00,
    "items": [
      {
        "id": 201,
        "product_id": 12,
        "quantity": 2,
        "price": 149.99,
        "subtotal": 299.98,
        "product": {
          "name": "Wireless Noise-Cancelling Headphones",
          "slug": "wireless-noise-cancelling-headphones"
        }
      }
    ]
  }
}
```

**Error: Insufficient Stock (422 Unprocessable Entity):**
```json
{
  "message": "Insufficient stock for 'Wireless Noise-Cancelling Headphones'. Requested 50, only 25 available.",
  "code": "INSUFFICIENT_STOCK",
  "details": {
    "product_id": 12,
    "product_name": "Wireless Noise-Cancelling Headphones",
    "requested_quantity": 50,
    "available_stock": 25
  }
}
```
> **Frontend Tip:** When receiving `INSUFFICIENT_STOCK`, show an inline alert next to that specific cart item and update the maximum selectable quantity to `available_stock`.

**Error: Card Declined (402 Payment Required):**
```json
{
  "message": "Payment authorization was declined. Transaction rolled back.",
  "code": "PAYMENT_FAILED"
}
```

---

#### 2. Customer Order History
- **Method**: `GET`
- **URL**: `/orders`
- **Auth**: `Bearer Token` (`customer`)

Returns paginated list of all orders placed by the current customer, including line items.

---

### D. Vendor Portal (`/vendor`)

#### 1. Get Vendor Profile & Earnings Summary
- **Method**: `GET`
- **URL**: `/vendor/profile`
- **Auth**: `Bearer Token` (`vendor`)

**Response (200 OK):**
```json
{
  "vendor": {
    "id": 2,
    "shop_name": "SoundWave Studio",
    "shop_description": "Premium audio gear.",
    "is_approved": true,
    "products_count": 14
  },
  "total_earnings": 1350.00
}
```

---

#### 2. Update Vendor Shop Details
- **Method**: `PUT`
- **URL**: `/vendor/profile`
- **Auth**: `Bearer Token` (`vendor`)

**Request Body:**
```json
{
  "shop_name": "SoundWave Studio Pro",
  "shop_description": "Studio-grade headphones and audio accessories."
}
```

---

#### 3. Create Product
- **Method**: `POST`
- **URL**: `/vendor/products`
- **Auth**: `Bearer Token` (`vendor` + Approved)

**Request Body:**
```json
{
  "name": "RGB Mechanical Keyboard",
  "description": "Tactile blue switches with programmable RGB.",
  "image": "https://example.com/img/keyboard.jpg",
  "price": 89.99,
  "stock_quantity": 50,
  "is_active": true,
  "flat_discount": null,
  "percentage_discount": null
}
```
> **Notice:** The frontend **does not** send a `slug`. The backend auto-generates a clean URL slug (e.g. `"rgb-mechanical-keyboard"`) and handles collision duplicates automatically.

**Error if Vendor is Not Approved (403 Forbidden):**
```json
{
  "message": "Your vendor account is pending approval. You cannot manage products until approved.",
  "code": "VENDOR_NOT_APPROVED"
}
```

---

#### 4. Update / Delete Product
- **Update**: `PUT /vendor/products/{id}`
- **Delete**: `DELETE /vendor/products/{id}`
- **Auth**: `Bearer Token` (`vendor` + Owner of product)

---

#### 5. Vendor Sales & Earnings Ledger
- **Orders list**: `GET /vendor/orders`
  Returns all customer orders that contain products from this vendor.
- **Earnings ledger**: `GET /vendor/earnings`
  Shows each order's commission breakdown:
  ```json
  {
    "order_amount": 200.00,
    "commission_fee": 20.00,
    "vendor_earning": 180.00,
    "created_at": "2026-10-20T06:12:00.000000Z"
  }
  ```

---

### E. Admin Portal (`/admin`)

*(Requires `auth:api` and user with `role: "admin"`)*

#### 1. List Vendors (Filter Pending Approvals)
- **Method**: `GET`
- **URL**: `/admin/vendors?is_approved=false`

#### 2. Approve Vendor
- **Method**: `PATCH`
- **URL**: `/admin/vendors/{id}/approve`

**Response (200 OK):**
```json
{
  "message": "Vendor 'SoundWave Studio' has been approved successfully.",
  "vendor": {
    "id": 2,
    "shop_name": "SoundWave Studio",
    "is_approved": true
  }
}
```

#### 3. Revoke / Reject Vendor
- **Method**: `PATCH`
- **URL**: `/admin/vendors/{id}/reject`

#### 4. Platform Analytics & Earnings
- **Ledger**: `GET /admin/earnings` (all vendor earnings & 10% platform cuts)
- **Stats**: `GET /admin/stats`
  ```json
  {
    "total_commission_collected": 1420.50,
    "total_vendor_payouts": 12784.50,
    "total_orders": 85,
    "total_products": 340,
    "total_vendors": 24,
    "pending_vendor_approvals": 3
  }
  ```

---

## 5. Frontend Implementation Checklist

- [ ] Store `access_token` securely in `localStorage` or `HttpOnly` cookie.
- [ ] Set up an Axios/Fetch interceptor to attach `Authorization: Bearer <token>` to requests.
- [ ] In the interceptor, listen for `401 Unauthorized` → clear storage and redirect to login.
- [ ] If logged in as `vendor` and `user.vendor.is_approved === false`, show an amber moderation banner on the vendor dashboard.
- [ ] On `/checkout`, catch `422 INSUFFICIENT_STOCK` and highlight the out-of-stock items.
