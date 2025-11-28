## Mobile API Documentation – Customer Module

### Introduction

This document describes the **Customer Mobile API module** for the Laravel application.
It exposes endpoints for:

- Customer registration and authentication
- Profile retrieval and update
- Password change
- Trip search and seat selection
- Ticket booking
- Booking history
- Booking payment confirmation

The API is built on Laravel 12 and uses **Laravel Sanctum** for token-based authentication.
All responses are JSON.

---

## Base URL

The API is served under the standard Laravel `/api` prefix:

- **Base URL**: `https://<your-domain>/api`

All endpoints below are relative to this base URL.

---

## Authentication

### Method: Laravel Sanctum API Tokens

- After successful **sign-up** or **sign-in**, the API returns a **plain-text API token**.
- Clients must send this token in the **Authorization header** as a Bearer token for protected endpoints.

**Header format:**

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

- Public endpoints (sign-up, sign-in, trip search, seat map) do **not** require authentication.
- Profile, booking creation, booking history, and payment endpoints **require** a valid Sanctum token.

---

## Endpoints List

### Auth & Profile

| Name                | Method | URL                                   | Auth      |
|---------------------|--------|----------------------------------------|-----------|
| Customer Sign-Up    | POST   | `/customer/auth/signup`               | Public    |
| Customer Sign-In    | POST   | `/customer/auth/login`                | Public    |
| Customer Logout     | POST   | `/customer/auth/logout`               | Sanctum   |
| Get Profile         | GET    | `/customer/profile`                   | Sanctum   |
| Update Profile      | PUT    | `/customer/profile`                   | Sanctum   |
| Change Password     | PUT    | `/customer/profile/password`          | Sanctum   |

### Trips, Seat Selection, Booking, Payment

| Name                   | Method | URL                                         | Auth      |
|------------------------|--------|--------------------------------------------|-----------|
| Search Trips           | GET    | `/customer/trips`                          | Public    |
| Trip Details / Seat Map| GET    | `/customer/trips/details`                  | Public    |
| Seat Map (alias)       | GET    | `/customer/trips/seat-map`                 | Public    |
| Legacy Available Routes| GET    | `/booking/available-routes`                | Public    |
| New Ticket Booking     | POST   | `/customer/bookings`                       | Sanctum   |
| Booking History        | GET    | `/customer/bookings`                       | Sanctum   |
| Confirm Booking Payment| POST   | `/customer/bookings/{booking}/payment`     | Sanctum   |

> All URLs above are relative to `/api`, e.g. `POST /api/customer/auth/signup`.

---

## Detailed Endpoint Documentation

---

### 1. Customer Sign-Up

**Endpoint Name:** Customer Sign-Up
**Method:** `POST`
**URL:** `/api/customer/auth/signup`
**Auth:** Public (no token)

#### Headers

| Header           | Required | Value                |
|------------------|----------|----------------------|
| `Accept`         | Yes      | `application/json`   |
| `Content-Type`   | Yes      | `application/json`   |

#### Request Body Fields

| Field       | Type   | Required | Description                                      | Validation Rules                                                                 |
|------------|--------|----------|--------------------------------------------------|----------------------------------------------------------------------------------|
| `name`     | string | Yes      | Customer full name                               | `required`, `string`, `max:255`                                                 |
| `email`    | string | Yes      | Customer email (unique, lowercased)             | `required`, `string`, `lowercase`, `email`, `max:255`, `unique:users,email`     |
| `password` | string | Yes      | Account password                                 | `required`, `confirmed`, `Rules\Password::defaults()` (Laravel default rules)   |
| `password_confirmation` | string | Yes  | Confirmation of the password            | Must match `password`                                                           |

#### Example Request JSON

```json
{
  "name": "John Doe",
  "email": "john.doe@example.com",
  "password": "StrongPass123!",
  "password_confirmation": "StrongPass123!"
}
```

#### Success Response

**Status:** `201 Created`

```json
{
  "success": true,
  "message": "Registration successful.",
  "data": {
    "token": "1|qwerty...plain-text-token",
    "user": {
      "id": 10,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "email_verified_at": null,
      "status": "Active",
      "roles": ["Customer"],
      "profile": {
        "phone": null,
        "cnic": null,
        "gender": null,
        "date_of_birth": null,
        "address": null
      },
      "created_at": "2025-11-25T10:00:00",
      "updated_at": "2025-11-25T10:00:00"
    }
  }
}
```

#### Validation Error Response

**Status:** `422 Unprocessable Entity`

```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": [
      "The email has already been taken."
    ]
  }
}
```

#### Notes

- Password strength is enforced using **Laravel’s default password rules** (minimum length, mixture of characters).
- On success, the user is registered and immediately provided with a Sanctum token for use in subsequent authenticated calls.

---

### 2. Customer Sign-In

**Endpoint Name:** Customer Sign-In
**Method:** `POST`
**URL:** `/api/customer/auth/login`
**Auth:** Public

#### Headers

Same as sign-up.

#### Request Body Fields

| Field      | Type   | Required | Description               | Validation Rules               |
|-----------|--------|----------|---------------------------|--------------------------------|
| `email`   | string | Yes      | Registered email address  | `required`, `string`, `email`  |
| `password`| string | Yes      | Account password          | `required`, `string`           |

#### Example Request JSON

```json
{
  "email": "john.doe@example.com",
  "password": "StrongPass123!"
}
```

#### Success Response

**Status:** `200 OK`

```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "token": "2|asdfgh...plain-text-token",
    "user": {
      "id": 10,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "email_verified_at": null,
      "status": "Active",
      "roles": ["Customer"],
      "profile": {
        "phone": "0300-1234567",
        "cnic": "12345-1234567-1",
        "gender": "male",
        "date_of_birth": "1990-01-01",
        "address": "Street 1, City"
      },
      "created_at": "2025-11-25T10:00:00",
      "updated_at": "2025-11-25T10:05:00"
    }
  }
}
```

#### Error Responses

- **Invalid credentials**

  **Status:** `422 Unprocessable Entity`

  ```json
  {
    "message": "These credentials do not match our records.",
    "errors": {
      "email": [
        "These credentials do not match our records."
      ]
    }
  }
  ```

- **Banned user**

  **Status:** `403 Forbidden`

  ```json
  {
    "success": false,
    "message": "Your account has been banned. Please contact an administrator to activate your account."
  }
  ```

- **2FA enabled (web-only flow)**

  **Status:** `403 Forbidden`

  ```json
  {
    "success": false,
    "message": "Two-factor authentication is enabled for this account. Please complete login via the web portal.",
    "code": "two_factor_required"
  }
  ```

#### Notes

- Under the hood, this uses the same **`LoginRequest`** as the web portal (rate limiting, auth.failed handling).
- Users with 2FA enabled cannot complete login via this mobile API; they must complete the 2FA flow through the web portal.

---

### 3. Customer Logout

**Endpoint Name:** Customer Logout
**Method:** `POST`
**URL:** `/api/customer/auth/logout`
**Auth:** Sanctum (Bearer token)

#### Headers

Include a valid token:

```http
Authorization: Bearer <token>
```

#### Request Body

No body.

#### Success Response

**Status:** `200 OK`

```json
{
  "success": true,
  "message": "Logged out successfully."
}
```

#### Notes

- Revokes **only the current access token** for the authenticated user.
- The user can still have other active tokens (e.g., another device).

---

### 4. Get Customer Profile

**Endpoint Name:** Get Profile
**Method:** `GET`
**URL:** `/api/customer/profile`
**Auth:** Sanctum

#### Headers

Bearer token required.

#### Request

No body.

#### Success Response

**Status:** `200 OK`

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 10,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "email_verified_at": null,
      "status": "Active",
      "roles": ["Customer"],
      "profile": {
        "phone": "0300-1234567",
        "cnic": "12345-1234567-1",
        "gender": "male",
        "date_of_birth": "1990-01-01",
        "address": "Street 1, City"
      },
      "created_at": "2025-11-25T10:00:00",
      "updated_at": "2025-11-25T10:05:00"
    }
  }
}
```

---

### 5. Update Customer Information

**Endpoint Name:** Update Profile
**Method:** `PUT`
**URL:** `/api/customer/profile`
**Auth:** Sanctum

#### Request Body Fields

Matches `ProfileUpdateRequest` used by the web portal.

| Field           | Type   | Required | Description                      | Validation Rules                                                                 |
|----------------|--------|----------|----------------------------------|----------------------------------------------------------------------------------|
| `name`         | string | Yes      | Customer name                    | `required`, `string`, `max:255`                                                 |
| `email`        | string | Yes      | Email (unique except own)        | `required`, `string`, `lowercase`, `email`, `max:255`, `unique:users,email,<id>`|
| `phone`        | string | No       | Phone number                     | `nullable`, `string`, `max:20`                                                  |
| `cnic`         | string | No       | CNIC in 12345-1234567-1 format   | `nullable`, `string`, `max:20`, `regex:/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/`          |
| `gender`       | string | No       | `male` or `female`               | `nullable`, `string`, `in:male,female`                                          |
| `date_of_birth`| date   | No       | Date of birth                    | `nullable`, `date`, `before:today`                                              |
| `address`      | string | No       | Address                          | `nullable`, `string`, `max:500`                                                 |

#### Example Request JSON

```json
{
  "name": "John Doe",
  "email": "john.doe@example.com",
  "phone": "0300-1234567",
  "cnic": "12345-1234567-1",
  "gender": "male",
  "date_of_birth": "1990-01-01",
  "address": "Street 1, City"
}
```

#### Success Response

**Status:** `200 OK`

```json
{
  "success": true,
  "message": "Profile updated successfully.",
  "data": {
    "user": {
      "id": 10,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "email_verified_at": null,
      "status": "Active",
      "roles": ["Customer"],
      "profile": {
        "phone": "0300-1234567",
        "cnic": "12345-1234567-1",
        "gender": "male",
        "date_of_birth": "1990-01-01",
        "address": "Street 1, City"
      },
      "created_at": "2025-11-25T10:00:00",
      "updated_at": "2025-11-25T10:10:00"
    }
  }
}
```

#### Error Example (CNIC format)

**Status:** `422 Unprocessable Entity`

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "cnic": [
      "The cnic format is invalid."
    ]
  }
}
```

#### Notes

- Changing `email` resets `email_verified_at` to `null`, same as the web portal behavior.
- Profile data is stored in the `Profile` model associated with the `User`.

---

### 6. Change Password

**Endpoint Name:** Change Password
**Method:** `PUT`
**URL:** `/api/customer/profile/password`
**Auth:** Sanctum

#### Request Body Fields

| Field                | Type   | Required | Description                           | Validation Rules                              |
|----------------------|--------|----------|---------------------------------------|-----------------------------------------------|
| `current_password`   | string | Yes      | Current password of the user          | `required`, `current_password`                |
| `password`           | string | Yes      | New password                          | `required`, `Password::defaults()`, `confirmed` |
| `password_confirmation` | string | Yes   | Confirmation of the new password      | Must match `password`                         |

#### Example Request JSON

```json
{
  "current_password": "OldPass123!",
  "password": "NewStrongPass123!",
  "password_confirmation": "NewStrongPass123!"
}
```

#### Success Response

**Status:** `200 OK`

```json
{
  "success": true,
  "message": "Password updated successfully."
}
```

#### Error Example

**Status:** `422 Unprocessable Entity`

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "current_password": [
      "The provided password does not match your current password."
    ]
  }
}
```

---

### 7. Search Trips (Available Trips)

**Endpoint Name:** Search Trips
**Method:** `GET`
**URL:** `/api/customer/trips`
**Auth:** Public

#### Query Parameters

| Param             | Type   | Required | Description                                 | Validation Rules                                 |
|-------------------|--------|----------|---------------------------------------------|--------------------------------------------------|
| `from_terminal_id`| int    | Yes      | Origin terminal ID                          | `required`, `exists:terminals,id`               |
| `to_terminal_id`  | int    | Yes      | Destination terminal ID                     | `required`, `exists:terminals,id`               |
| `date`            | string | Yes      | Travel date (YYYY-MM-DD)                    | `required`, `date_format:Y-m-d`, `after_or_equal:today` |

#### Example Request

```http
GET /api/customer/trips?from_terminal_id=1&to_terminal_id=5&date=2025-12-01
Accept: application/json
```

#### Success Response

**Status:** `200 OK`

```json
{
  "success": true,
  "data": {
    "trips": [
      {
        "trip_id": 101,
        "timetable_id": 12,
        "route_id": 3,
        "route_name": "City A - City B",
        "departure_time": "08:00 AM",
        "arrival_time": "12:00 PM",
        "departure_datetime": "2025-12-01 08:00:00",
        "fare": {
          "final_fare": 1500,
          "currency": "PKR"
        },
        "available_seats": 20,
        "bus_name": "Luxury Express"
      }
    ]
  }
}
```

#### Error Examples

- **Same origin and destination**

  **Status:** `400 Bad Request`

  ```json
  {
    "success": false,
    "message": "From and To terminals must be different"
  }
  ```

- **Validation errors**

  **Status:** `422 Unprocessable Entity`

  ```json
  {
    "message": "The given data was invalid.",
    "errors": {
      "date": [
        "The date does not match the format Y-m-d."
      ]
    }
  }
  ```

#### Notes

- Only trips at least **2 hours ahead of the current time** are returned (online booking restriction).
- Uses the same timetable and trip creation logic as the web portal (`TimetableStop`, `TripFactoryService`, `AvailabilityService`).

---

### 8. Trip Details / Seat Map

**Endpoint Names:**

- Trip Details / Seat Map
- Seat Map (alias)

**Methods / URLs:**

- `GET /api/customer/trips/details`
- `GET /api/customer/trips/seat-map` (alias, same behavior)

**Auth:** Public

#### Query Parameters

| Param             | Type   | Required | Description                                 | Validation Rules                               |
|-------------------|--------|----------|---------------------------------------------|-----------------------------------------------|
| `trip_id`         | int    | Yes      | Trip ID                                     | `required`, `exists:trips,id`                 |
| `from_terminal_id`| int    | Yes      | Origin terminal ID                          | `required`, `exists:terminals,id`             |
| `to_terminal_id`  | int    | Yes      | Destination terminal ID                     | `required`, `exists:terminals,id`             |
| `platform`        | string | No       | `android`, `ios`, `web`, or `counter`       | `nullable`, `in:android,ios,web,counter`      |

- If `platform` is omitted, defaults to `android`.
- Discount calculation uses this `platform` value.

#### Example Request

```http
GET /api/customer/trips/details?trip_id=101&from_terminal_id=1&to_terminal_id=5&platform=android
Accept: application/json
```

#### Success Response

**Status:** `200 OK`

```json
{
  "success": true,
  "data": {
    "trip": {
      "id": 101,
      "route_name": "City A - City B",
      "bus_name": "Luxury Express",
      "departure_date": "2025-12-01",
      "departure_datetime": "2025-12-01 08:00:00"
    },
    "from_stop": {
      "terminal_name": "Terminal A",
      "terminal_code": "TA",
      "departure_at": "2025-12-01 08:00:00"
    },
    "to_stop": {
      "terminal_name": "Terminal B",
      "terminal_code": "TB",
      "arrival_at": "2025-12-01 12:00:00"
    },
    "fare": {
      "base_fare": 1500,
      "final_fare": 1500,
      "currency": "PKR",
      "discount_type": "percentage",
      "discount_value": 10,
      "discount_amount": 0,
      "has_discount": true
    },
    "seat_map": {
      "1": { "number": 1, "status": "available" },
      "2": { "number": 2, "status": "booked", "gender": "female" },
      "3": { "number": 3, "status": "held" }
    },
    "available_count": 20
  }
}
```

#### Error Examples

- **Invalid segment** (from > to):

  **Status:** `400 Bad Request`

  ```json
  {
    "success": false,
    "message": "Invalid segment selection"
  }
  ```

- **Within 2 hours of departure**:

  **Status:** `400 Bad Request`

  ```json
  {
    "success": false,
    "message": "Online bookings must be made at least 2 hours before departure. This trip departs too soon."
  }
  ```

#### Notes

- Seat statuses:
  - `available`: seat can be booked.
  - `held`: temporarily held/reserved.
  - `booked`: already confirmed on overlapping segment (with best-known gender where applicable).
- This is the **primary endpoint for seat selection** in the mobile app.

---

### 9. New Ticket Booking

**Endpoint Name:** New Ticket Booking
**Method:** `POST`
**URL:** `/api/customer/bookings`
**Auth:** Sanctum

#### Headers

Bearer token required.

#### Request Body Fields

| Field                 | Type        | Required | Description                                       | Validation Rules                                  |
|-----------------------|-------------|----------|---------------------------------------------------|---------------------------------------------------|
| `trip_id`             | int         | Yes      | Trip ID                                           | `required`, `exists:trips,id`                     |
| `from_terminal_id`    | int         | Yes      | Origin terminal ID                                | `required`, `exists:terminals,id`                 |
| `to_terminal_id`      | int         | Yes      | Destination terminal ID                           | `required`, `exists:terminals,id`                 |
| `seat_numbers`        | int[]       | Yes      | Array of seat numbers to book                     | `required`, `array`, `min:1`, each `integer`      |
| `seats_data`          | object[]    | Yes      | Array of seat data objects                        | `required`, `array`, `min:1`                      |
| `seats_data[].seat_number` | int   | Yes      | Seat number                                       | `required`, `integer`                             |
| `seats_data[].gender` | string      | Yes      | Passenger gender for this seat                    | `required`, `string`                              |
| `passengers`          | object[]    | Yes      | Passenger list                                    | `required`, `array`, `min:1`                      |
| `passengers[].name`   | string      | Yes      | Passenger name                                    | `required`, `string`                              |
| `passengers[].gender` | string      | No       | Passenger gender                                  | `nullable`, `string`                              |
| `passengers[].cnic`   | string      | No       | Passenger CNIC                                    | `nullable`, `string`                              |
| `passengers[].phone`  | string      | No       | Passenger phone                                   | `nullable`, `string`                              |
| `passengers[].email`  | string      | No       | Passenger email                                   | `nullable`, `string`                              |
| `total_fare`          | number      | Yes      | Total fare before discounts/taxes                | `required`, `numeric`, `min:0`                    |
| `discount_amount`     | number      | No       | Total discount amount                             | `nullable`, `numeric`, `min:0`                    |
| `tax_amount`          | number      | No       | Total tax amount                                  | `nullable`, `numeric`, `min:0`                    |
| `final_amount`        | number      | Yes      | Final payable amount                              | `required`, `numeric`, `min:0`                    |

#### Example Request JSON

```json
{
  "trip_id": 101,
  "from_terminal_id": 1,
  "to_terminal_id": 5,
  "seat_numbers": [1, 2],
  "seats_data": [
    { "seat_number": 1, "gender": "male" },
    { "seat_number": 2, "gender": "female" }
  ],
  "passengers": [
    { "name": "John Doe", "gender": "male", "cnic": "12345-1234567-1" },
    { "name": "Jane Doe", "gender": "female" }
  ],
  "total_fare": 3000,
  "discount_amount": 300,
  "tax_amount": 0,
  "final_amount": 2700
}
```

#### Success Response

**Status:** `201 Created`

```json
{
  "success": true,
  "message": "Booking created successfully.",
  "data": {
    "booking": {
      "id": 500,
      "booking_number": "B00A1B2",
      "status": "hold",
      "channel": "online",
      "payment_status": "unpaid",
      "payment_method": "mobile_wallet",
      "total_fare": 3000,
      "discount_amount": 300,
      "tax_amount": 0,
      "final_amount": 2700,
      "currency": "PKR",
      "total_passengers": 2,
      "reserved_until": "2025-12-01 07:45:00",
      "confirmed_at": null,
      "trip": {
        "id": 101,
        "route_name": "City A - City B",
        "bus_name": "Luxury Express",
        "departure_date": "2025-12-01",
        "departure_datetime": "2025-12-01 08:00:00",
        "estimated_arrival_datetime": "2025-12-01 12:00:00"
      },
      "from_stop": {
        "id": 10,
        "terminal_name": "Terminal A",
        "terminal_code": "TA",
        "sequence": 1
      },
      "to_stop": {
        "id": 20,
        "terminal_name": "Terminal B",
        "terminal_code": "TB",
        "sequence": 5
      },
      "seats": [
        {
          "id": 1,
          "seat_number": "1",
          "gender": "male",
          "fare": 1500,
          "tax_amount": 0,
          "final_amount": 1500
        },
        {
          "id": 2,
          "seat_number": "2",
          "gender": "female",
          "fare": 1500,
          "tax_amount": 0,
          "final_amount": 1500
        }
      ],
      "passengers": [
        {
          "id": 1,
          "name": "John Doe",
          "age": null,
          "gender": "male",
          "cnic": "12345-1234567-1",
          "phone": null,
          "email": null,
          "status": "active"
        },
        {
          "id": 2,
          "name": "Jane Doe",
          "age": null,
          "gender": "female",
          "cnic": null,
          "phone": null,
          "email": null,
          "status": "active"
        }
      ],
      "created_at": "2025-11-25 10:20:00"
    }
  }
}
```

#### Error Examples

- **Within 2 hours of departure**

  **Status:** `422 Unprocessable Entity`

  ```json
  {
    "success": false,
    "errors": {
      "departure_time": [
        "Online bookings must be made at least 2 hours before departure. This trip departs too soon to book online."
      ]
    }
  }
  ```

- **Invalid seats or overlapping segments**

  **Status:** `400 Bad Request`

  ```json
  {
    "success": false,
    "message": "Seat 2 not available for this segment."
  }
  ```

#### Notes

- Booking is created with:
  - `status`: `hold`
  - `payment_status`: `unpaid`
  - `reserved_until`: current time + 15 minutes
- Seats and passengers are persisted via `BookingService`, which also:
  - Revalidates seat availability under a DB lock.
  - Prevents overlapping seat allocations on the same segment.

---

### 10. Booking History

**Endpoint Name:** Booking History
**Method:** `GET`
**URL:** `/api/customer/bookings`
**Auth:** Sanctum

#### Query Parameters

Standard Laravel pagination parameters are supported (e.g. `page`).

#### Example Request

```http
GET /api/customer/bookings?page=1
Authorization: Bearer <token>
Accept: application/json
```

#### Success Response

**Status:** `200 OK`

```json
{
  "success": true,
  "data": [
    {
      "id": 500,
      "booking_number": "B00A1B2",
      "status": "confirmed",
      "channel": "online",
      "payment_status": "paid",
      "payment_method": "mobile_wallet",
      "total_fare": 3000,
      "discount_amount": 300,
      "tax_amount": 0,
      "final_amount": 2700,
      "currency": "PKR",
      "total_passengers": 2,
      "reserved_until": null,
      "confirmed_at": "2025-12-01 07:50:00",
      "trip": { "...": "..." },
      "from_stop": { "...": "..." },
      "to_stop": { "...": "..." },
      "seats": [ /* seat objects */ ],
      "passengers": [ /* passenger objects */ ],
      "created_at": "2025-11-25 10:20:00"
    }
  ],
  "links": {
    "first": "https://<your-domain>/api/customer/bookings?page=1",
    "last": "https://<your-domain>/api/customer/bookings?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "https://<your-domain>/api/customer/bookings",
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

#### Notes

- History includes bookings where:
  - The authenticated user is the creator (`user_id`), **or**
  - Any passenger CNIC in the booking matches the user’s profile CNIC.
- Results are ordered by most recent (`created_at` descending).
- Uses `BookingResource` for each entry, matching the booking show format.

---

### 11. Confirm Booking Payment

**Endpoint Name:** Confirm Booking Payment
**Method:** `POST`
**URL:** `/api/customer/bookings/{booking}/payment`
**Auth:** Sanctum

#### Path Parameters

| Param      | Type | Description              |
|-----------|------|--------------------------|
| `booking` | int  | Booking ID (route model) |

#### Request Body Fields

| Field           | Type   | Required | Description                     | Validation Rules                          |
|----------------|--------|----------|---------------------------------|-------------------------------------------|
| `payment_method` | string | Yes    | `easypaisa` or `jazzcash`       | `required`, `in:easypaisa,jazzcash`       |
| `transaction_id` | string | Yes    | Gateway transaction reference   | `required`, `string`, `max:100`           |

#### Example Request

```http
POST /api/customer/bookings/500/payment
Authorization: Bearer <token>
Content-Type: application/json

{
  "payment_method": "easypaisa",
  "transaction_id": "TXN-987654321"
}
```

#### Success Response

**Status:** `200 OK`

```json
{
  "success": true,
  "message": "Payment successful! Your booking has been confirmed.",
  "data": {
    "booking": {
      "id": 500,
      "booking_number": "B00A1B2",
      "status": "confirmed",
      "payment_status": "paid",
      "payment_method": "mobile_wallet",
      "online_transaction_id": "TXN-987654321",
      "trip": { "...": "..." },
      "from_stop": { "...": "..." },
      "to_stop": { "...": "..." },
      "seats": [ /* seat objects */ ],
      "passengers": [ /* passenger objects */ ],
      "confirmed_at": "2025-12-01 07:50:00",
      "reserved_until": null,
      "created_at": "2025-11-25 10:20:00"
    }
  }
}
```

#### Error Examples

- **Unauthorized access to booking (different user)**

  **Status:** `403 Forbidden`

  ```json
  {
    "success": false,
    "message": "Unauthorized access to this booking."
  }
  ```

- **Booking expired**

  **Status:** `400 Bad Request`

  ```json
  {
    "success": false,
    "message": "Booking has expired. Please create a new booking."
  }
  ```

- **Payment verification failed**

  **Status:** `422 Unprocessable Entity`

  ```json
  {
    "success": false,
    "message": "Payment verification failed. Please try again or contact support."
  }
  ```

- **Unexpected error**

  **Status:** `500 Internal Server Error`

  ```json
  {
    "success": false,
    "message": "An error occurred while processing your payment. Please try again."
  }
  ```

#### Notes

- Payment verification is currently **mocked**, similar to the web `PaymentController`:
  - Logs verification request.
  - Treats any non-empty `transaction_id` as successful.
- On success, the same business rules as the portal are applied via `BookingService::confirmPayment`:
  - Sets `payment_status` to `paid`,
  - Sets `status` to `confirmed`,
  - Clears `reserved_until`.

---

This completes the documentation for the **Customer Mobile API module** including all requested flows: sign-up, sign-in, password change, profile update, trip search, seat selection, new booking creation, booking history, and payment confirmation.


