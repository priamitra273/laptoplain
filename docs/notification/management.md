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
        Backend->>Redis: Cache::store('redis')->get("notifications:user:{userId}", [])
        alt Cache has new notifications
            Backend->>Browser: event: notification (json notif data per item)
            Browser->>Browser: unshift notif + increment unreadCount
            Backend->>Redis: Cache::store('redis')->forget(key)
        end
        opt 30s sejak last ping
            Backend->>Browser: : ping (SSE keepalive comment)
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
const currentCount = unreadCount.value           // save previous
const notif = notifications.value.find((n) => n.id === notificationId)
try {
    unreadCount.value--                           // optimistic update
    if (notif) notif.is_read = true               // optimistic update
    await axios.post(route('notifications.read', { encoded: notificationId }))
} catch (error) {
    unreadCount.value = currentCount              // rollback
    if (notif) notif.is_read = false              // rollback
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

Notifikasi dibuat lewat facade `TaskNotification` (mengarah ke `TaskNotificationService`).
Pemicunya antara lain: listener `NotifyAssignedUsers` (event `TaskCreated`),
`TaskObserver`, `CreateTaskAction`/`UpdateTaskAction`, `TaskService`,
`CommentService`, dan `ProjectController`.

```php
// TaskNotificationService::createTaskNotification($task, $userIds, TaskNotificationType $type)
$notification = Notification::create([
    'task_id' => $task->id,
    'task_status_id' => $task->status_id,
    'task_type_id' => $task->type_id,
    'message' => ...,
]);

foreach ($userIds as $userId) {
    $notification->users()->attach($userId, ['is_read' => false]); // Pivot

    $payload = ['id' => Sqids::encode(...), 'message' => ..., 'task_id' => Sqids::encode(...), 'is_read' => false];

    $key = "notifications:user:$userId";
    $existing = Cache::store('redis')->get($key, []);
    $existing[] = $payload;
    Cache::store('redis')->put($key, $existing, now()->addMinutes(1)); // TTL 1 menit
}
```

**Note:** ID notifikasi & task pada payload Redis sudah di-encode Sqids. Notifikasi juga dikirim ke user dengan role `watcher-admin` (digabung ke `$userIds`).

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
