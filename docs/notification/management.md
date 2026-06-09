# Notification Management

Sistem notifikasi real-time menggunakan Server-Sent Events (SSE) dengan EventSource di frontend dan Redis cache di backend.

---

## 1. List Notifications

```mermaid
sequenceDiagram
    Browser->>Backend: GET /notifications
    Backend->>DB: Notification::with(users where user_id)
    Note over DB: whereHas users + orderBy created_at desc
    Backend->>Backend: Extract id, message, task_id, is_read from pivot
    Backend->>Backend: Sqids encode all IDs
    Backend->>Browser: JSON [{id, message, task_id, is_read}]
```

## 2. SSE Stream (Real-time)

```mermaid
sequenceDiagram
    Browser->>Browser: new EventSource('/notifications/stream')

    Browser->>Backend: GET /notifications/stream
    Backend->>Backend: set_time_limit(0) + output buffers flush
    Backend->>Backend: ob_implicit_flush(true)

    Backend->>DB: Load initial notifications + pivot is_read
    Backend->>Browser: event: init (json initial data)
    Browser->>Browser: Set unreadCount = filter(not is_read).length

    loop While connection not aborted (every 200ms)
        Backend->>Redis: GET notifications:user:{userId} from cache
        alt Cache has new notifications
            Backend->>Browser: event: notification (json notif data)
            Browser->>Browser: push notif + increment unreadCount
            Backend->>Redis: DEL cache key
        else Cache empty + 30s since last ping
            Backend->>Browser: ping (SSE keepalive comment)
        end
        Backend->>Backend: usleep(200_000)
    end

    Browser->>Browser: On error: close EventSource + cleanup
```

**SSE Response Headers:**
- `Content-Type: text/event-stream`
- `Cache-Control: no-cache`
- `Connection: keep-alive`
- `Content-Encoding: none`
- `X-Accel-Buffering: no` (untuk nginx)

## 3. Mark As Read

```mermaid
sequenceDiagram
    Browser->>Backend: POST /notifications/{encoded}/read
    Backend->>Backend: Sqids::decode(encoded)
    Backend->>DB: Notification::findOrFail(id)
    Backend->>DB: updateExistingPivot(userId, is_read=true)
    Backend->>Browser: JSON {success: true}
```

### Frontend (NotificationProvider.vue)

```typescript
// markAsRead with optimistic update + rollback
const currentCount = unreadCount.value  // save previous
unreadCount.value--                      // optimistic update
notif.is_read = true                     // optimistic update
try {
    await axios.post(route('notifications.read', {encoded: notificationId}))
} catch (error) {
    unreadCount.value = currentCount     // rollback
    notif.is_read = false                 // rollback
}
```

## 4. Clear All

```mermaid
sequenceDiagram
    Browser->>Backend: POST /notifications/clear
    Backend->>DB: user.notifications().detach()
    Note over DB: Hapus semua pivot records
    Backend->>Browser: JSON {success: true}
```

### Frontend

```typescript
// clearNotifications with optimistic update
const currentCount = unreadCount.value
try {
    unreadCount.value = 0
    await axios.post(route('notifications.clear'))
    notifications.value = []
} catch (error) {
    unreadCount.value = currentCount  // rollback
}
```

## Redis Notification Queue

Saat notifikasi dibuat oleh service (TaskService/CommentService):

```php
// TaskNotificationService::createTaskNotification()
Notification::create([...])        // Simpan ke DB
$user->notifications()->attach()   // Pivot: is_read = false
Redis::rpush("notifications:user:$userId", json_encode(notif))
Redis::expire("notifications:user:$userId", 60)  // TTL 1 menit
```

**Note:** Notifikasi juga dikirim ke user dengan role `watcher-admin`.

## Routes

| Method | URI | Controller |
|--------|-----|------------|
| GET | `/notifications` | `NotificationController@index` |
| GET | `/notifications/stream` | `NotificationController@stream` |
| POST | `/notifications/{encoded}/read` | `NotificationController@markAsRead` |
| POST | `/notifications/clear` | `NotificationController@clearAll` |

## Frontend

| File | Purpose |
|------|---------|
| `provider/NotificationProvider.vue` | SSE EventSource + provide notif state |
