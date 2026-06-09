# Login

Halaman login menggunakan layout dua kolom: kiri (background image + gradient overlay + logo) dan kanan (form).

## Flow Backend

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Backend
    participant DB

    User->>Browser: GET /login
    Browser->>Backend: GET /login (guest middleware)
    Backend->>Browser: Inertia render('auth/Login', { canResetPassword, status })
    Browser-->>User: Split layout: bg image + form

    User->>Browser: Isi email, password, remember me
    Browser->>Backend: POST /login (guest middleware)

    Backend->>Backend: Validasi LoginRequest
    Note over Backend: rules: email=required|string|email<br/>password=required|string

    Backend->>Backend: Rate limiting (throttleKey)
    Backend->>Backend: Auth::attempt(['email','password'])
    alt Success
        Backend->>Backend: session()->regenerate()
        Backend->>Browser: Redirect intended to /dashboard
    else Gagal
        Backend->>Backend: RateLimiter::hit()
        Backend->>Browser: Redirect back with errors + email (old input)
        Browser-->>User: Tampilkan error validation
    end
```

## Logout

```mermaid
sequenceDiagram
    User->>Browser: Klik Logout
    Browser->>Backend: POST /logout (auth middleware)
    Backend->>Backend: Auth::guard('web')->logout()
    Backend->>Backend: session()->invalidate()
    Backend->>Backend: session()->regenerateToken()
    Backend->>Browser: Redirect ke /
```

## Validasi (LoginRequest)

| Field | Rule |
|-------|------|
| `email` | required, string, email |
| `password` | required, string |

## Frontend (Login.vue)

**Teknologi:** Inertia `useForm`, PrimeVue (InputText, Password with toggleMask, Checkbox, Button)

**Perilaku:**
- `form.post(route('login'))`, pada `onFinish` reset password field
- `remember` field dikirim sebagai boolean
- `canResetPassword` diambil dari `Route::has('password.request')`

## Routes

| Method | URI | Controller | Middleware | Name |
|--------|-----|------------|------------|------|
| GET | `/login` | `AuthenticatedSessionController@create` | `guest` | `login` |
| POST | `/login` | `AuthenticatedSessionController@store` | `guest` | — |
| POST | `/logout` | `AuthenticatedSessionController@destroy` | `auth` | `logout` |
