# VoiceChat — API Reference

Base URL: `https://your-host/api`
All endpoints return JSON.
Auth: `Authorization: Bearer <access_token>`

---

## Authentication

### POST /auth/register
```json
{
  "username": "string (3-30, alphanumeric+underscore)",
  "email": "valid email",
  "password": "min 6 chars",
  "display_name": "optional",
  "gender": "male|female|other",
  "country": "optional"
}
```

Response 201:
```json
{
  "success": true,
  "message": "Account created",
  "data": {
    "user": { "id": 1, "username": "..." },
    "access_token": "...",
    "refresh_token": "...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

### POST /auth/login
```json
{ "login": "username_or_email", "password": "...", "device": "iOS App" }
```

### POST /auth/refresh
```json
{ "refresh_token": "..." }
```

### GET /me
Returns the current user.

---

## Users

### GET /users/{username}
Public profile + stats.

### POST /users/{userId}/follow
### POST /users/{userId}/unfollow
### POST /users/{userId}/block
### POST /users/{userId}/unblock
### POST /users/{userId}/report
```json
{ "reason": "spam", "description": "optional" }
```

---

## Rooms

### GET /rooms?type=public&category=music&q=keyword&page=1
### GET /rooms/{id}
### POST /rooms/{id}/join
Optional: `{ "password": "..." }` for password-protected rooms.

### POST /rooms/{id}/leave

### POST /rooms/{id}/seat
```json
{ "seat_index": 0, "action": "take|leave|swap|lock|unlock" }
```

### POST /rooms/{id}/mic
```json
{ "action": "mute|unmute|kick", "target": 123 }
```

### POST /rooms/{id}/hand
```json
{ "action": "raise|lower|accept|reject", "target": 123 }
```

### POST /rooms/{id}/chat
```json
{ "content": "Hello!", "type": "text" }
```

### POST /rooms/{id}/signaling
```json
{ "to": 123, "type": "offer|answer|ice|bye", "payload": { /* sdp or candidate */ } }
```

### GET /rooms/{id}/messages?before=123
### GET /rooms/{id}/participants

---

## Gifts

### GET /gifts
List of available gifts.

### POST /gifts/send
```json
{
  "gift_id": 5,
  "receiver_id": 123,
  "room_id": 7,        // optional
  "agency_id": 2,      // optional
  "quantity": 1,
  "message": "Great talk!",  // optional
  "anonymous": false
}
```

### GET /gifts/history?direction=received|sent

---

## Messages (1:1 Chat)

### GET /messages
Returns inbox (conversations list).

### GET /messages/{userId}
Returns conversation messages.

### POST /messages/{userId}/send
```json
{
  "content": "Hi there!",
  "type": "text|image|voice|file",
  "media_url": "/uploads/...",
  "reply_to": 123,
  "metadata": {}
}
```

### POST /messages/{userId}/read
### POST /messages/{userId}/typing

---

## Notifications

### GET /notifications?unread=1
### POST /notifications/{id}/read
### POST /notifications/read-all

---

## Agencies

### GET /agencies?q=&verified=1
### GET /agencies/{id}
### POST /agencies/{id}/join

---

## Other

### GET /leaderboard?type=users|agencies|rooms
### GET /search?q=keyword
### GET /search/{type}?q=keyword

---

## Error responses

```json
{ "success": false, "error": "Validation failed", "errors": { "email": ["Invalid email"] } }
```

HTTP codes:
- 200 OK
- 201 Created
- 400 Bad Request
- 401 Unauthorized
- 403 Forbidden
- 404 Not Found
- 419 CSRF (web only)
- 422 Validation
- 429 Rate limit
- 500 Server error
