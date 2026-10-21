# Multi-Vendor Marketplace — Frontend API Integration Guide (Lovable & Web/Mobile)

Welcome to the **Multi-Vendor Marketplace API**. This guide provides complete end-to-end documentation for frontend developers (Lovable, React, Vite, Next.js, Flutter/React Native).

---

## 1. Quick Start: Frontend `.env` Configuration

Create or update your `.env` file in your frontend application (e.g. in Lovable, Vite, or Next.js):

```env
# ==============================================================
# FRONTEND ENVIRONMENT CONFIGURATION (LOVABLE / REACT / VITE)
# ==============================================================

# 1. LIVE NGROK TUNNEL (For remote testing / Lovable sandbox):
VITE_API_BASE_URL=https://usage-putt-sage.ngrok-free.dev/api

# 2. LOCAL DEV SERVER (For local testing on same machine):
# VITE_API_BASE_URL=http://localhost:8000/api

# Marketplace Currency Configuration:
VITE_DEFAULT_CURRENCY=PKR
VITE_CURRENCY_SYMBOL=Rs.

# Supported Payment Gateways:
VITE_SUPPORTED_PAYMENT_METHODS=jazzcash,easypaisa,cod
```

> **IMPORTANT FOR NGROK FREE TIER:**  
> When testing via the Ngrok URL, always send this header to bypass Ngrok's browser security warning page:  
> `ngrok-skip-browser-warning: true`

---

## 2. Standard Headers & Error Handling

### Request Headers
Send these headers with every request:
```http
Accept: application/json
Content-Type: application/json
ngrok-skip-browser-warning: true
```

### Protected Request Headers
For endpoints requiring authentication, attach the Bearer token:
```http
Authorization: Bearer <your_jwt_access_token>
```

### Standardized User-Friendly Error Format
No raw database errors or PHP stack traces are ever exposed to the client. Every error returns:

```json
{
  "success": false,
  "message": "Validation failed. Please check the submitted fields.",
  "code": "VALIDATION_ERROR",
  "errors": {
    "username": ["Please provide a username."],
    "email": ["Please provide a valid email address."],
    "password": ["Password is required."]
  }
}
```

### Error Codes Reference

| HTTP Status | Error `code` | Description | Recommended Frontend Handling |
|---|---|---|---|
| `401 Unauthorized` | `UNAUTHENTICATED` | Bearer token is missing, expired, or invalid. | Clear stored token and redirect user to `/login`. |
| `403 Forbidden` | `VENDOR_NOT_APPROVED` | Vendor shop has not been approved by Admin yet. | Show banner: *"Your shop profile is awaiting admin approval"*. |
| `403 Forbidden` | `FORBIDDEN_ROLE` | User does not have the required role for this portal. | Show *"Access Denied — Insufficient Permissions"*. |
| `403 Forbidden` | `NOT_PRODUCT_OWNER` | Vendor tried to update or delete another vendor's item. | Show alert: *"You do not own this product"*. |
| `404 Not Found` | `ENDPOINT_NOT_FOUND` | The requested route does not exist. | Show 404 page. |
| `404 Not Found` | `RESOURCE_NOT_FOUND` | Product slug or Order ID was not found. | Show *"Item not found"*. |
| `422 Unprocessable` | `VALIDATION_ERROR` | Required fields were empty or formatted incorrectly. | Highlight invalid input fields using the `errors` dictionary. |
| `422 Unprocessable` | `INSUFFICIENT_STOCK` | Stock ran out during atomic checkout. | Show toast: *"Selected quantity exceeds available stock"*. |
| `402 Payment Req.` | `PAYMENT_FAILED` | Gateway (JazzCash / Easypaisa) declined transaction. | Prompt customer to select another payment method. |

---

## 3. Roles & System Workflows

The platform has 3 distinct roles:
1. **`customer`**: Browses public products, places checkout orders in PKR via JazzCash, Easypaisa, or COD, and views their order history.
2. **`vendor`**: Signs up with shop name. Starts in `is_approved: false`. Once approved by admin, can manage catalog, view sales, and track 90% payout earnings.
3. **`admin`**: Platform owner who reviews vendor applications, approves/rejects shops, and monitors the 10% commission ledger and analytics.

---

## 4. Complete Endpoints Reference (With Full URLs)

### A. Authentication Routes

#### 1. Register User / Vendor
- **Method**: `POST`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/auth/register`
- **Local URL**: `http://localhost:8000/api/auth/register`
- **Auth**: Public

**Request Body (Customer):**
```json
{
  "username": "usman_khan",
  "email": "usman@gmail.com",
  "password": "Password123!",
  "role": "customer"
}
```

**Request Body (Vendor):**
```json
{
  "username": "lahoretraders",
  "email": "vendor@lahoretraders.pk",
  "password": "Password123!",
  "role": "vendor",
  "shop_name": "Lahore Traders Mall",
  "shop_description": "Premier clothing and electronics vendor."
}
```

**Response (`201 Created`):**
```json
{
  "message": "User registered successfully.",
  "user": {
    "id": 1,
    "username": "lahoretraders",
    "email": "vendor@lahoretraders.pk",
    "role": "vendor",
    "vendor": {
      "id": 1,
      "shop_name": "Lahore Traders Mall",
      "shop_description": "Premier clothing and electronics vendor.",
      "is_approved": false
    }
  },
  "access_token": "eyJ0eXAiOiJKV1QiLC...",
  "token_type": "bearer",
  "expires_in": 86400
}
```

---

#### 2. Login
- **Method**: `POST`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/auth/login`
- **Local URL**: `http://localhost:8000/api/auth/login`
- **Auth**: Public

**Request Body:**
```json
{
  "email": "usman@gmail.com",
  "password": "Password123!"
}
```

**Response (`200 OK`):**
```json
{
  "message": "Login successful.",
  "user": {
    "id": 1,
    "username": "usman_khan",
    "email": "usman@gmail.com",
    "role": "customer",
    "vendor": null
  },
  "access_token": "eyJ0eXAiOiJKV1QiLC...",
  "token_type": "bearer",
  "expires_in": 86400
}
```

---

#### 3. Current User (`/auth/me`)
- **Method**: `GET`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/auth/me`
- **Local URL**: `http://localhost:8000/api/auth/me`
- **Auth**: Bearer Token required

**Response (`200 OK`):**
```json
{
  "user": {
    "id": 1,
    "username": "usman_khan",
    "email": "usman@gmail.com",
    "role": "customer",
    "vendor": null
  }
}
```

---

#### 4. Refresh Token
- **Method**: `POST`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/auth/refresh`
- **Local URL**: `http://localhost:8000/api/auth/refresh`
- **Auth**: Bearer Token required

**Response (`200 OK`):**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLC...",
  "token_type": "bearer",
  "expires_in": 86400
}
```

---

#### 5. Logout
- **Method**: `POST`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/auth/logout`
- **Local URL**: `http://localhost:8000/api/auth/logout`
- **Auth**: Bearer Token required

**Response (`200 OK`):**
```json
{
  "message": "Successfully logged out."
}
```

---

### B. Public Product Catalog

#### 1. Browse Products (Filter, Search, Paginate)
- **Method**: `GET`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/products?search=kurta&vendor_id=1&per_page=12&page=1`
- **Local URL**: `http://localhost:8000/api/products?search=kurta&vendor_id=1&per_page=12&page=1`
- **Auth**: Public

**Query Parameters:**
- `search` *(optional)*: Search by product title.
- `vendor_id` *(optional)*: Filter products by vendor ID.
- `per_page` *(optional, default 15)*: Number of items per page.
- `page` *(optional, default 1)*: Page number.

**Response (`200 OK`):**
```json
{
  "data": [
    {
      "id": 1,
      "vendor_id": 1,
      "name": "Khaadi Men Kurta 2026",
      "slug": "khaadi-men-kurta-2026",
      "description": "Premium summer stitched kurta.",
      "image": "https://example.com/kurta.jpg",
      "price": "4500.00",
      "stock_quantity": 48,
      "total_sold": 2,
      "is_active": true,
      "flat_discount": "0.00",
      "percentage_discount": 0,
      "vendor": {
        "id": 1,
        "shop_name": "Lahore Traders Mall",
        "is_approved": true
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 1,
    "per_page": 12
  }
}
```

---

#### 2. Get Product Detail by Slug
- **Method**: `GET`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/products/{slug}`
- **Local URL**: `http://localhost:8000/api/products/{slug}`
- **Auth**: Public

**Example:** `GET https://usage-putt-sage.ngrok-free.dev/api/products/khaadi-men-kurta-2026`

**Response (`200 OK`):**
```json
{
  "data": {
    "id": 1,
    "vendor_id": 1,
    "name": "Khaadi Men Kurta 2026",
    "slug": "khaadi-men-kurta-2026",
    "description": "Premium summer stitched kurta.",
    "price": "4500.00",
    "stock_quantity": 48,
    "total_sold": 2,
    "is_active": true,
    "vendor": {
      "id": 1,
      "shop_name": "Lahore Traders Mall",
      "is_approved": true
    }
  }
}
```

---

### C. Customer Checkout & Orders

#### 1. Place Checkout Order
- **Method**: `POST`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/checkout`
- **Local URL**: `http://localhost:8000/api/checkout`
- **Auth**: Bearer Token required (`customer` or `admin`)

**Request Body:**
```json
{
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ],
  "payment_method": "jazzcash",
  "shipping_amount": 250.00
}
```

> **Supported `payment_method` values:** `"jazzcash"`, `"easypaisa"`, `"cod"`

**Response (`201 Created`):**
```json
{
  "message": "Order placed successfully.",
  "order": {
    "id": 1,
    "order_number": "ORD-ZRQC8TMXFQ",
    "user_id": 2,
    "total_amount": "9250.00",
    "status": "processing",
    "payment_method": "jazzcash",
    "payment_status": "paid",
    "shipping_amount": "250.00",
    "created_at": "2026-10-21T04:20:00.000000Z"
  },
  "payment_result": {
    "success": true,
    "transaction_id": "JC-1729484400-9821",
    "payment_method": "jazzcash",
    "amount": 9250,
    "currency": "PKR",
    "status": "paid",
    "message": "JazzCash payment processed successfully."
  }
}
```

---

#### 2. Customer Order History
- **Method**: `GET`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/orders?per_page=10&page=1`
- **Local URL**: `http://localhost:8000/api/orders?per_page=10&page=1`
- **Auth**: Bearer Token required (`customer` or `admin`)

**Response (`200 OK`):**
```json
{
  "data": [
    {
      "id": 1,
      "order_number": "ORD-ZRQC8TMXFQ",
      "total_amount": "9250.00",
      "status": "processing",
      "payment_method": "jazzcash",
      "payment_status": "paid",
      "shipping_amount": "250.00",
      "created_at": "2026-10-21T04:20:00.000000Z",
      "items": [
        {
          "id": 1,
          "product_id": 1,
          "quantity": 2,
          "price": "4500.00",
          "total": "9000.00",
          "product": {
            "id": 1,
            "name": "Khaadi Men Kurta 2026",
            "slug": "khaadi-men-kurta-2026",
            "vendor": {
              "id": 1,
              "shop_name": "Lahore Traders Mall"
            }
          }
        }
      ]
    }
  ]
}
```

---

#### 3. Single Order Details
- **Method**: `GET`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/orders/{order_id}`
- **Local URL**: `http://localhost:8000/api/orders/{order_id}`
- **Auth**: Bearer Token required

---

### D. Vendor Portal (`/vendor`)

All vendor routes require `role: vendor`.

#### 1. Get Vendor Profile & Earnings
- **Method**: `GET`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/vendor/profile`
- **Local URL**: `http://localhost:8000/api/vendor/profile`
- **Auth**: Bearer Token (`vendor`)

**Response (`200 OK`):**
```json
{
  "vendor": {
    "id": 1,
    "shop_name": "Lahore Traders Mall",
    "shop_description": "Premier clothing vendor.",
    "is_approved": true,
    "products_count": 5
  },
  "total_earnings": 8100.00
}
```

---

#### 2. Update Vendor Shop Profile
- **Method**: `PUT`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/vendor/profile`
- **Local URL**: `http://localhost:8000/api/vendor/profile`
- **Auth**: Bearer Token (`vendor`)

**Request Body:**
```json
{
  "shop_name": "Lahore Traders Flagship",
  "shop_description": "Official flagship outlet in Lahore."
}
```

---

#### 3. Vendor Product Catalog
- **Method**: `GET`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/vendor/products`
- **Local URL**: `http://localhost:8000/api/vendor/products`
- **Auth**: Bearer Token (`vendor`)

---

#### 4. Add Product (Requires Admin Approval)
- **Method**: `POST`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/vendor/products`
- **Local URL**: `http://localhost:8000/api/vendor/products`
- **Auth**: Bearer Token (`vendor` + `vendor.approved`)

> **Automatic Slug:** No need to ask the user for a slug! The backend generates it automatically.

**Request Body:**
```json
{
  "name": "Men Embroidered Kurta 2026",
  "description": "100% fine cotton fabric.",
  "price": 5200.00,
  "stock_quantity": 25,
  "is_active": true,
  "percentage_discount": 10
}
```

**Response (`201 Created`):**
```json
{
  "message": "Product created successfully.",
  "product": {
    "id": 2,
    "name": "Men Embroidered Kurta 2026",
    "slug": "men-embroidered-kurta-2026",
    "price": "5200.00",
    "stock_quantity": 25,
    "is_active": true
  }
}
```

---

#### 5. Update Product
- **Method**: `PUT`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/vendor/products/{product_id}`
- **Local URL**: `http://localhost:8000/api/vendor/products/{product_id}`
- **Auth**: Bearer Token (`vendor` + `vendor.approved`)

---

#### 6. Delete Product
- **Method**: `DELETE`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/vendor/products/{product_id}`
- **Local URL**: `http://localhost:8000/api/vendor/products/{product_id}`
- **Auth**: Bearer Token (`vendor` + `vendor.approved`)

---

#### 7. Vendor Sold Orders
- **Method**: `GET`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/vendor/orders`
- **Local URL**: `http://localhost:8000/api/vendor/orders`
- **Auth**: Bearer Token (`vendor`)

---

#### 8. Vendor Earnings Breakdown
- **Method**: `GET`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/vendor/earnings`
- **Local URL**: `http://localhost:8000/api/vendor/earnings`
- **Auth**: Bearer Token (`vendor`)

---

### E. Admin Portal (`/admin`)

All admin routes require `role: admin`.

#### 1. List Vendors (With Approval Filter)
- **Method**: `GET`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/admin/vendors?is_approved=false`
- **Local URL**: `http://localhost:8000/api/admin/vendors?is_approved=false`
- **Auth**: Bearer Token (`admin`)

---

#### 2. Approve Vendor Shop
- **Method**: `PATCH`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/admin/vendors/{vendor_id}/approve`
- **Local URL**: `http://localhost:8000/api/admin/vendors/{vendor_id}/approve`
- **Auth**: Bearer Token (`admin`)

**Response (`200 OK`):**
```json
{
  "message": "Vendor 'Lahore Traders Mall' has been approved successfully.",
  "vendor": {
    "id": 1,
    "shop_name": "Lahore Traders Mall",
    "is_approved": true
  }
}
```

---

#### 3. Reject / Revoke Vendor Approval
- **Method**: `PATCH`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/admin/vendors/{vendor_id}/reject`
- **Local URL**: `http://localhost:8000/api/admin/vendors/{vendor_id}/reject`
- **Auth**: Bearer Token (`admin`)

---

#### 4. Platform Earnings Ledger
- **Method**: `GET`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/admin/earnings`
- **Local URL**: `http://localhost:8000/api/admin/earnings`
- **Auth**: Bearer Token (`admin`)

---

#### 5. Platform Dashboard Stats
- **Method**: `GET`
- **Live URL**: `https://usage-putt-sage.ngrok-free.dev/api/admin/stats`
- **Local URL**: `http://localhost:8000/api/admin/stats`
- **Auth**: Bearer Token (`admin`)

**Response (`200 OK`):**
```json
{
  "total_commission_collected": 900.00,
  "total_vendor_payouts": 8100.00,
  "total_orders": 1,
  "total_products": 2,
  "total_vendors": 1,
  "pending_vendor_approvals": 0
}
```

---

## 5. Lovable / React Integration Tips

1. **Persist the Bearer Token:**
   Save `response.access_token` into `localStorage.setItem('auth_token', token)`.
2. **Setup an Axios / Fetch Interceptor:**
   ```typescript
   import axios from 'axios';

   const api = axios.create({
     baseURL: import.meta.env.VITE_API_BASE_URL,
     headers: {
       'Accept': 'application/json',
       'Content-Type': 'application/json',
       'ngrok-skip-browser-warning': 'true',
     },
   });

   api.interceptors.request.use((config) => {
     const token = localStorage.getItem('auth_token');
     if (token) {
       config.headers.Authorization = `Bearer ${token}`;
     }
     return config;
   });

   export default api;
   ```
3. **Displaying Prices in PKR:**
   ```typescript
   export const formatPKR = (amount: number | string) => {
     return `Rs. ${Number(amount).toLocaleString('en-PK')}`;
   };
   ```
