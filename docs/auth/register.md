# Register

Pendaftaran pengguna baru. **Routes saat ini dinonaktifkan** (di-comment di `routes/auth.php` lines 15-18).

## Flow Backend

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Backend
    participant DB

    User->>Browser: GET /register
    Browser->>Backend: GET /register
    Backend->>Browser: Inertia render('auth/Register')

    User->>Browser: Isi name, email, password, password_confirmation
    Browser->>Backend: POST /register

    Backend->>Backend: Validasi
    Note over Backend: name=required|string|max:255<br/>email=required|string|lowercase|email|max:255|unique:users<br/>password=required|confirmed|Password::defaults()

    Backend->>DB: User::create({name, email, hashedPassword, is_active=true})
    Backend->>DB: Update created_by & updated_by = self (user->id)
    Backend->>DB: $user->assignRole('user-user')
    Backend->>Backend: event(new Registered($user))
    Backend->>Backend: Auth::login($user)
    Backend->>Browser: Redirect to dashboard with success flash
```

## Validasi

| Field | Rule |
|-------|------|
| `name` | required, string, max:255 |
| `email` | required, string, lowercase, email, max:255, unique:users |
| `password` | required, confirmed, Password::defaults() |

## Routes (nonaktif)

| Method | URI | Controller |
|--------|-----|------------|
| GET | `/register` | `RegisteredUserController@create` |
| POST | `/register` | `RegisteredUserController@store` |
