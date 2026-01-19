# API Documentation - Ds Framework

Complete API endpoints for the framework.

## Base URL

```
http://localhost:8000
```

## Response Format

All responses are JSON:

```json
{
  "status": "success",
  "data": {},
  "error": null
}
```

## Status Codes

- `200` OK - Request successful
- `201` Created - Resource created
- `204` No Content - Request successful, no content
- `400` Bad Request - Invalid request
- `401` Unauthorized - Not authenticated
- `403` Forbidden - Not authorized
- `404` Not Found - Resource not found
- `500` Internal Server Error - Server error

---

## Endpoints

### Home

#### GET /

Get homepage.

**Response:**
```
HTTP/1.1 200 OK

Welcome to the Framework!
```

---

### Health Check

#### GET /health

Check application health.

**Response:**
```json
{
  "status": "ok",
  "timestamp": 1640000000
}
```

---

### Users

#### GET /users

List all users.

**Query Parameters:**
- `page` (integer, optional) - Page number (default: 1)
- `limit` (integer, optional) - Items per page (default: 10)
- `sort` (string, optional) - Sort field (default: id)
- `order` (string, optional) - Sort order: asc or desc (default: asc)

**Response:**
```json
[
  {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  {
    "id": 2,
    "name": "Jane Smith",
    "email": "jane@example.com"
  }
]
```

#### POST /users

Create a new user.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secret123"
}
```

**Response (201 Created):**
```json
{
  "id": 3,
  "name": "John Doe",
  "email": "john@example.com"
}
```

#### GET /users/{id}

Get a specific user.

**URL Parameters:**
- `id` (integer, required) - User ID

**Response:**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com"
}
```

**Error Response (404):**
```json
{
  "error": "User not found"
}
```

#### PUT /users/{id}

Update a user.

**URL Parameters:**
- `id` (integer, required) - User ID

**Request Body:**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com"
}
```

**Response:**
```json
{
  "id": 1,
  "name": "Jane Doe",
  "email": "jane@example.com"
}
```

#### DELETE /users/{id}

Delete a user.

**URL Parameters:**
- `id` (integer, required) - User ID

**Response (204 No Content):**
```
HTTP/1.1 204 No Content
```

---

### API Routes (with /api prefix)

#### GET /api/ping

Simple ping endpoint.

**Response:**
```json
{
  "message": "pong"
}
```

---

## Example Requests

### Using cURL

```bash
# GET request
curl http://localhost:8000/users

# GET with query parameters
curl "http://localhost:8000/users?page=2&limit=5"

# POST request
curl -X POST http://localhost:8000/users \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@example.com"}'

# PUT request
curl -X PUT http://localhost:8000/users/1 \
  -H "Content-Type: application/json" \
  -d '{"name":"Jane"}'

# DELETE request
curl -X DELETE http://localhost:8000/users/1
```

### Using JavaScript/Fetch

```javascript
// GET
fetch('http://localhost:8000/users')
  .then(r => r.json())
  .then(data => console.log(data));

// POST
fetch('http://localhost:8000/users', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    name: 'John',
    email: 'john@example.com'
  })
})
.then(r => r.json())
.then(data => console.log(data));

// PUT
fetch('http://localhost:8000/users/1', {
  method: 'PUT',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ name: 'Jane' })
})
.then(r => r.json())
.then(data => console.log(data));

// DELETE
fetch('http://localhost:8000/users/1', { method: 'DELETE' })
.then(r => r.status === 204 ? null : r.json())
.then(() => console.log('Deleted'));
```

### Using Python

```python
import requests
import json

# GET
response = requests.get('http://localhost:8000/users')
users = response.json()
print(users)

# POST
new_user = {
    'name': 'John',
    'email': 'john@example.com'
}
response = requests.post('http://localhost:8000/users', json=new_user)
user = response.json()
print(user)

# PUT
updated_data = {'name': 'Jane'}
response = requests.put('http://localhost:8000/users/1', json=updated_data)
user = response.json()
print(user)

# DELETE
response = requests.delete('http://localhost:8000/users/1')
print(response.status_code)  # 204
```

---

## Error Handling

### Common Errors

#### 404 Not Found
```json
{
  "error": "Not Found"
}
```

#### 400 Bad Request
```json
{
  "error": "Invalid input",
  "details": {
    "email": "Email is required"
  }
}
```

#### 500 Internal Server Error
```json
{
  "error": "Internal Server Error",
  "message": "Something went wrong"
}
```

---

## Headers

### Request Headers

```
Content-Type: application/json
Authorization: Bearer {token}
```

### Response Headers

```
Content-Type: application/json; charset=utf-8
X-Response-Time: 0.042
X-Request-ID: abc123
```

---

## Authentication (Coming Soon)

### Login

```bash
POST /auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "secret123"
}
```

Response:
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "name": "John",
    "email": "john@example.com"
  }
}
```

### Protected Routes

Use token in Authorization header:

```bash
GET /api/users
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

---

## WebSocket (Real-time)

### Connect

```javascript
const ws = new WebSocket('ws://localhost:6001/ws');

ws.onopen = () => {
  console.log('Connected');
};

ws.onmessage = (event) => {
  const message = JSON.parse(event.data);
  console.log('Received:', message);
};

ws.onerror = (error) => {
  console.error('Error:', error);
};

ws.onclose = () => {
  console.log('Disconnected');
};
```

### Send Message

```javascript
ws.send(JSON.stringify({
  action: 'message',
  data: 'Hello Server'
}));
```

### Broadcast Events

Messages are broadcast to all connected clients:

```javascript
// Server broadcasts
// Client receives
ws.onmessage = (event) => {
  const message = JSON.parse(event.data);
  console.log('Broadcast:', message);
};
```

---

## Rate Limiting (Coming Soon)

Requests are limited to:
- 60 requests per minute (per IP)
- 1000 requests per hour (per user)

Response headers:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1640000060
```

---

## Pagination

List endpoints support pagination:

```bash
GET /users?page=2&limit=10
```

Response:
```json
{
  "data": [...],
  "pagination": {
    "page": 2,
    "limit": 10,
    "total": 150,
    "pages": 15
  }
}
```

---

## Sorting & Filtering

List endpoints support sorting:

```bash
# Sort by name, ascending
GET /users?sort=name&order=asc

# Sort by created date, descending
GET /users?sort=created_at&order=desc

# Filter by status
GET /users?status=active
```

---

## Changelog

### v1.0.0 (Current)
- ✅ User management
- ✅ REST API
- ✅ WebSocket support
- ✅ Real-time broadcasting

### Upcoming
- 🔄 Authentication (OAuth2)
- 🔄 Database migrations
- 🔄 Advanced filtering
- 🔄 File uploads
- 🔄 Notifications
