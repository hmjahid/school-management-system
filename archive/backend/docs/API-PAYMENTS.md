# Payment API Documentation

This document provides detailed information about the Payment API endpoints for the School Management System.

## Base URL

```
https://api.yourschool.edu/v1
```

## Authentication

All endpoints require authentication using Bearer tokens.

```http
Authorization: Bearer your_access_token_here
```

## Payment Gateway Endpoints

### List Available Gateways

Retrieve a list of available payment gateways.

```http
GET /api/payments/gateways
```

#### Response

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "bKash",
      "code": "bkash",
      "type": "mobile_banking",
      "is_online": true,
      "logo_url": "https://example.com/gateways/bkash.png",
      "description": "Mobile banking payment method",
      "fee_percentage": 1.5,
      "fee_fixed": 10,
      "min_amount": 100,
      "max_amount": 50000,
      "currency": "BDT",
      "supported_currencies": ["BDT"],
      "is_configured": true
    },
    {
      "id": 2,
      "name": "Cash",
      "code": "cash",
      "type": "offline",
      "is_online": false,
      "logo_url": "https://example.com/gateways/cash.png",
      "description": "Pay with cash at the office",
      "fee_percentage": 0,
      "fee_fixed": 0,
      "min_amount": 1,
      "max_amount": 100000,
      "currency": "BDT",
      "supported_currencies": ["BDT", "USD"],
      "is_configured": true
    }
  ]
}
```

## Payment Endpoints

### Initialize a Payment

Initialize a new payment with the selected gateway.

```http
POST /api/payments/initiate
```

#### Request Body

```json
{
  "gateway": "bkash",
  "amount": 1000,
  "currency": "BDT",
  "paymentable_type": "tuition",
  "paymentable_id": 123,
  "description": "Tuition fee payment for April 2023",
  "return_url": "https://yourschool.edu/payments/success",
  "cancel_url": "https://yourschool.edu/payments/cancel",
  "metadata": {
    "student_id": "STU-2023-001",
    "invoice_id": "INV-2023-0042"
  }
}
```

#### Response (Success)

```json
{
  "success": true,
  "message": "Payment initiated successfully",
  "data": {
    "payment": {
      "id": "pay_abc123xyz",
      "invoice_number": "INV-2023-0042",
      "amount": 1000,
      "currency": "BDT",
      "payment_method": "bkash",
      "payment_status": "pending",
      "created_at": "2023-04-15T10:30:00Z"
    },
    "gateway": {
      "redirect_url": "https://checkout.bkash.com/pay/abc123",
      "expires_at": "2023-04-15T10:45:00Z"
    }
  }
}
```

### Check Payment Status

Check the status of a payment.

```http
GET /api/payments/status/{payment_id}
```

#### Response

```json
{
  "success": true,
  "data": {
    "id": "pay_abc123xyz",
    "invoice_number": "INV-2023-0042",
    "amount": 1000,
    "paid_amount": 1000,
    "due_amount": 0,
    "currency": "BDT",
    "payment_method": "bkash",
    "payment_status": "completed",
    "transaction_id": "TXN123456789",
    "payment_date": "2023-04-15T10:32:15Z",
    "created_at": "2023-04-15T10:30:00Z",
    "updated_at": "2023-04-15T10:32:15Z",
    "payment_details": {
      "description": "Tuition fee payment for April 2023",
      "metadata": {
        "student_id": "STU-2023-001",
        "invoice_id": "INV-2023-0042"
      },
      "fee_percentage": 1.5,
      "fee_fixed": 10,
      "fee_amount": 25,
      "return_url": "https://yourschool.edu/payments/success",
      "cancel_url": "https://yourschool.edu/payments/cancel"
    }
  }
}
```

### List Payments

Retrieve a paginated list of payments with optional filters.

```http
GET /api/payments?status=completed&gateway=bkash&start_date=2023-04-01&end_date=2023-04-30&per_page=10&page=1
```

#### Query Parameters

| Parameter  | Type    | Description                                  |
|------------|---------|----------------------------------------------|
| status     | string  | Filter by payment status                     |
| gateway    | string  | Filter by payment gateway                    |
| start_date | date    | Filter by start date (YYYY-MM-DD)            |
| end_date   | date    | Filter by end date (YYYY-MM-DD)              |
| per_page   | integer | Number of items per page (default: 15)       |
| page       | integer | Page number (default: 1)                     |

#### Response

```json
{
  "data": [
    {
      "id": "pay_abc123xyz",
      "invoice_number": "INV-2023-0042",
      "amount": 1000,
      "currency": "BDT",
      "payment_method": "bkash",
      "payment_status": "completed",
      "created_at": "2023-04-15T10:30:00Z"
    },
    {
      "id": "pay_def456uvw",
      "invoice_number": "INV-2023-0041",
      "amount": 1500,
      "currency": "BDT",
      "payment_method": "nagad",
      "payment_status": "completed",
      "created_at": "2023-04-14T09:15:00Z"
    }
  ],
  "links": {
    "first": "https://api.yourschool.edu/v1/payments?page=1",
    "last": "https://api.yourschool.edu/v1/payments?page=5",
    "prev": null,
    "next": "https://api.yourschool.edu/v1/payments?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "https://api.yourschool.edu/v1/payments",
    "per_page": 15,
    "to": 15,
    "total": 72
  }
}
```

### Process Refund

Initiate a refund for a completed payment.

```http
POST /api/payments/{payment_id}/refund
```

#### Request Body

```json
{
  "amount": 500,
  "reason": "Overpayment refund",
  "notes": "Customer was charged twice by mistake"
}
```

#### Response (Success)

```json
{
  "success": true,
  "message": "Refund processed successfully",
  "data": {
    "id": "ref_abc123xyz",
    "payment_id": "pay_abc123xyz",
    "amount": 500,
    "currency": "BDT",
    "status": "pending",
    "reason": "Overpayment refund",
    "created_at": "2023-04-16T14:30:00Z"
  }
}
```

## Webhook Integration

Payment gateways will send webhook notifications to the following endpoint:

```
POST /api/payments/webhook/{gateway}
```

### Webhook Headers

| Header               | Description                             |
|----------------------|-----------------------------------------|
| X-Webhook-Signature | Signature for webhook verification      |
| X-Webhook-Event     | Event type (e.g., payment.completed)    |
| X-Webhook-Delivery  | Unique ID for the webhook delivery     |

### Webhook Payload Example (bKash)

```json
{
  "event": "payment.completed",
  "data": {
    "paymentID": "TRX123456789",
    "merchantInvoiceNumber": "INV-2023-0042",
    "amount": 1000,
    "currency": "BDT",
    "transactionStatus": "Completed",
    "initiationTime": "2023-04-15T10:30:00Z",
    "completedTime": "2023-04-15T10:32:15Z",
    "customerMsisdn": "88017XXXXXXXX"
  }
}
```

### Webhook Response

Your webhook endpoint should respond with a `200 OK` status code and the following JSON:

```json
{
  "success": true,
  "message": "Webhook processed successfully"
}
```

## Error Handling

### Error Responses

All error responses follow this format:

```json
{
  "success": false,
  "error": {
    "code": "error_code",
    "message": "Human-readable error message",
    "details": {
      "field_name": ["Validation error message"]
    }
  }
}
```

### Common Error Codes

| HTTP Status | Error Code               | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | invalid_request          | Invalid request parameters                       |
| 401         | unauthorized            | Authentication required or invalid token         |
| 403         | forbidden               | Insufficient permissions                         |
| 404         | not_found               | Resource not found                               |
| 422         | validation_failed       | Request validation failed                        |
| 429         | too_many_requests       | Rate limit exceeded                              |
| 500         | server_error            | Internal server error                            |
| 503         | service_unavailable     | Service temporarily unavailable                  |

## Rate Limiting

API requests are rate limited to protect the service from abuse:

- **Authenticated requests**: 100 requests per minute per user
- **Unauthenticated requests**: 20 requests per minute per IP
- **Payment endpoints**: 10 requests per minute per IP

Exceeding these limits will result in a `429 Too Many Requests` response.

## Testing

### Test Cards

Use the following test card numbers for payment gateway testing:

| Gateway | Card Number       | Expiry   | CVV  | Result          |
|---------|-------------------|----------|------|-----------------|
| bKash   | 01700000000       | Any      | Any  | Success         |
| bKash   | 01700000001       | Any      | Any  | Insufficient    |
| bKash   | 01700000002       | Any      | Any  | Failed          |

### Sandbox Environment

Use the following base URLs for testing:

- **API**: `https://sandbox-api.yourschool.edu/v1`
- **bKash**: `https://checkout.sandbox.bka.sh/v1.2.0-beta`
- **Nagad**: `https://sandbox.mynagad.com`

## Support

For assistance with the Payment API, please contact:

- **Email**: api-support@yourschool.edu
- **Phone**: +880 XXXX-XXXXXX
- **Hours**: Sunday-Thursday, 9:00 AM - 5:00 PM (GMT+6)

## Changelog

### v1.0.0 (2023-04-15)
- Initial release of Payment API
- Support for bKash, Nagad, and Rocket payments
- Webhook integration for payment notifications
