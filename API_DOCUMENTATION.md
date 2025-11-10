# API Documentation

This document describes the RESTful API endpoints provided by the Laravel Serial Pattern package.

## Authentication

All API endpoints require authentication using **Laravel Sanctum**. Include your API token in the request header:

```bash
Authorization: Bearer {your-api-token}
```

## Base URL

```
/api/v1/serial-numbers
```

## Rate Limiting

API endpoints are rate-limited per pattern type to prevent abuse:
- **Default**: 60 requests per minute per pattern
- Rate limit headers are included in responses:
  - `X-RateLimit-Limit`: Total requests allowed
  - `X-RateLimit-Remaining`: Remaining requests in current window

---

## Endpoints

### 1. Generate Serial Number

Generate a new serial number for a specified pattern.

**Endpoint**: `POST /api/v1/serial-numbers/generate`

**Request Body**:
```json
{
  "type": "invoice",
  "model_type": "App\\Models\\Invoice",
  "model_id": 123,
  "context": {
    "department_id": 5
  }
}
```

**Parameters**:
- `type` (required): Pattern name configured in `config/serial-pattern.php`
- `model_type` (optional): Full class name of the associated model
- `model_id` (optional): ID of the associated model instance
- `context` (optional): Additional context data for segment resolution

**Response** (201 Created):
```json
{
  "success": true,
  "data": {
    "serial": "INV-2025-11-01234",
    "log": {
      "id": 1,
      "serial": "INV-2025-11-01234",
      "pattern_name": "invoice",
      "model_type": "App\\Models\\Invoice",
      "model_id": 123,
      "user_id": 1,
      "generated_at": "2025-11-10T12:34:56Z",
      "voided_at": null,
      "void_reason": null,
      "is_void": false,
      "created_at": "2025-11-10T12:34:56Z",
      "updated_at": "2025-11-10T12:34:56Z"
    }
  },
  "message": "Serial number generated successfully"
}
```

**Error Responses**:
- `400 Bad Request`: Invalid pattern or context
- `404 Not Found`: Model not found (if model_type and model_id provided)
- `429 Too Many Requests`: Rate limit exceeded

**Example**:
```bash
curl -X POST https://api.example.com/api/v1/serial-numbers/generate \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "invoice",
    "model_type": "App\\Models\\Invoice",
    "model_id": 123
  }'
```

---

### 2. Preview Serial Number

Preview the next serial number without actually generating it.

**Endpoint**: `GET /api/v1/serial-numbers/{type}/peek`

**URL Parameters**:
- `type` (required): Pattern name

**Query Parameters**:
- `model_type` (optional): Full class name of the associated model
- `model_id` (optional): ID of the associated model instance
- `context[key]` (optional): Additional context data

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "preview": "INV-2025-11-01235",
    "type": "invoice"
  },
  "message": "Serial number preview generated"
}
```

**Example**:
```bash
curl -X GET "https://api.example.com/api/v1/serial-numbers/invoice/peek" \
  -H "Authorization: Bearer {token}"
```

---

### 3. Reset Sequence Counter

Reset a pattern's sequence counter to a specified value.

**Endpoint**: `POST /api/v1/serial-numbers/{type}/reset`

**URL Parameters**:
- `type` (required): Pattern name

**Request Body**:
```json
{
  "start_value": 1000
}
```

**Parameters**:
- `start_value` (optional): New starting number (defaults to pattern's configured start value)

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Sequence reset successfully"
}
```

**Error Responses**:
- `404 Not Found`: Sequence not found

**Example**:
```bash
curl -X POST https://api.example.com/api/v1/serial-numbers/invoice/reset \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"start_value": 1000}'
```

---

### 4. Void Serial Number

Mark a serial number as voided (cancelled/invalid).

**Endpoint**: `POST /api/v1/serial-numbers/{serial}/void`

**URL Parameters**:
- `serial` (required): The serial number to void

**Request Body**:
```json
{
  "reason": "Duplicate entry - original transaction cancelled"
}
```

**Parameters**:
- `reason` (optional): Explanation for voiding (max 500 characters)

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "serial": "INV-2025-11-01234",
    "pattern_name": "invoice",
    "model_type": "App\\Models\\Invoice",
    "model_id": 123,
    "user_id": 1,
    "generated_at": "2025-11-10T12:34:56Z",
    "voided_at": "2025-11-10T13:00:00Z",
    "void_reason": "Duplicate entry - original transaction cancelled",
    "is_void": true,
    "created_at": "2025-11-10T12:34:56Z",
    "updated_at": "2025-11-10T13:00:00Z"
  },
  "message": "Serial number voided successfully"
}
```

**Error Responses**:
- `404 Not Found`: Serial number not found

**Example**:
```bash
curl -X POST https://api.example.com/api/v1/serial-numbers/INV-2025-11-01234/void \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"reason": "Transaction cancelled"}'
```

---

### 5. Query Serial Logs

Retrieve serial number logs with filtering and pagination.

**Endpoint**: `GET /api/v1/serial-numbers/logs`

**Query Parameters**:
- `pattern_name` (optional): Filter by pattern name
- `is_void` (optional): Filter by void status (true/false)
- `user_id` (optional): Filter by user ID
- `from_date` (optional): Filter by generation date (from)
- `to_date` (optional): Filter by generation date (to)
- `per_page` (optional): Results per page (1-100, default: 15)

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "serial": "INV-2025-11-01234",
      "pattern_name": "invoice",
      "model_type": "App\\Models\\Invoice",
      "model_id": 123,
      "user_id": 1,
      "generated_at": "2025-11-10T12:34:56Z",
      "voided_at": null,
      "void_reason": null,
      "is_void": false,
      "created_at": "2025-11-10T12:34:56Z",
      "updated_at": "2025-11-10T12:34:56Z"
    }
  ],
  "links": {
    "first": "https://api.example.com/api/v1/serial-numbers/logs?page=1",
    "last": "https://api.example.com/api/v1/serial-numbers/logs?page=10",
    "prev": null,
    "next": "https://api.example.com/api/v1/serial-numbers/logs?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

**Example**:
```bash
curl -X GET "https://api.example.com/api/v1/serial-numbers/logs?pattern_name=invoice&is_void=false&per_page=25" \
  -H "Authorization: Bearer {token}"
```

---

## Configuration

Enable the API in your `config/serial-pattern.php`:

```php
'api' => [
    'enabled' => env('SERIAL_API_ENABLED', true),
    'prefix' => 'api/v1/serial-numbers',
    'middleware' => ['api', 'auth:sanctum'],
    
    'rate_limit' => [
        'enabled' => true,
        'max_attempts' => 60,
        'decay_minutes' => 1,
    ],
],
```

Set in your `.env`:
```env
SERIAL_API_ENABLED=true
```

## Security Best Practices

1. **Use HTTPS**: Always use HTTPS in production
2. **Token Rotation**: Rotate API tokens regularly
3. **Scopes**: Use Sanctum token abilities to limit access
4. **Rate Limiting**: Monitor and adjust rate limits based on usage
5. **Logging**: Enable activity logging for audit trails

## Error Handling

All error responses follow this format:

```json
{
  "success": false,
  "message": "Error description"
}
```

Common HTTP status codes:
- `200 OK`: Successful request
- `201 Created`: Resource created successfully
- `400 Bad Request`: Invalid input
- `401 Unauthorized`: Missing or invalid authentication
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation failed
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Server error

## Webhooks (Coming Soon)

Future versions will support webhooks for:
- Serial number generated
- Serial number voided
- Sequence reset
- Pattern collision detected

---

## Support

For issues or questions:
- GitHub Issues: https://github.com/azaharizaman/laravel-serial-numbering/issues
- Email: azaharizaman@gmail.com
