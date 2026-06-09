# Password Confirmation

Konfirmasi password untuk mengakses area sensitif aplikasi.

## Flow

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Backend

    User->>Browser: Akses area yang butuh password confirmation
    Note over Browser: Middleware password.confirm

    Browser->>Backend: GET /confirm-password (auth middleware)
    Backend->>Browser: Inertia render('auth/ConfirmPassword')

    User->>Browser: Input password
    Browser->>Backend: POST /confirm-password (auth middleware)

    Note over Backend: Validasi: password=required|current_password
    Note over Backend: current_password rule: Auth::guard()->validate({email, password})

    alt Password benar
        Backend->>Backend: session(['auth.password_confirmed_at' => time()])
        Backend->>Browser: Redirect intended (ke halaman yang diminta)
    else Password salah
        Backend->>Browser: Validation error "The password is incorrect"
        Browser-->>User: Tampilkan error
    end
```

## Validasi

| Field | Rule |
|-------|------|
| `password` | required, current_password |

**`current_password` rule:** memvalidasi password terhadap user yang sedang login via `Auth::guard()->validate()`.

## Session

Setelah konfirmasi sukses, `auth.password_confirmed_at` disimpan di session. Middleware `password.confirm` akan mengecek session ini untuk mengizinkan akses. Default timeout: 3 jam (10800 detik).

## Routes

| Method | URI | Controller | Middleware | Name |
|--------|-----|------------|------------|------|
| GET | `/confirm-password` | `ConfirmablePasswordController@show` | `auth` | `password.confirm` |
| POST | `/confirm-password` | `ConfirmablePasswordController@store` | `auth` | — |
