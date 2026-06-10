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

    Note over Backend: Auth::guard('web')->validate(<br/>{email: user->email, password})

    alt Password benar
        Backend->>Backend: session()->put('auth.password_confirmed_at', time())
        Backend->>Browser: redirect()->intended(route('dashboard'))
    else Password salah
        Backend->>Browser: ValidationException(['password' => __('auth.password')])
        Browser-->>User: Tampilkan error
    end
```

## Validasi

Tidak ada FormRequest atau rule `current_password`. Controller memvalidasi password secara manual:

```php
Auth::guard('web')->validate([
    'email' => $request->user()->email,
    'password' => $request->password,
]);
```

Jika gagal, dilempar `ValidationException` dengan pesan `__('auth.password')` pada field `password`.

## Session

Setelah konfirmasi sukses, `auth.password_confirmed_at` disimpan di session. Middleware `password.confirm` akan mengecek session ini untuk mengizinkan akses. Default timeout: 3 jam (10800 detik).

## Routes

| Method | URI | Controller | Middleware | Name |
|--------|-----|------------|------------|------|
| GET | `/confirm-password` | `ConfirmablePasswordController@show` | `auth` | `password.confirm` |
| POST | `/confirm-password` | `ConfirmablePasswordController@store` | `auth` | — |
