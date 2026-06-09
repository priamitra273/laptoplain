# Password Reset

Flow reset password menggunakan token yang dikirim via email.

## Flow

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Backend
    participant Mailer

    User->>Browser: GET /forgot-password
    Browser->>Backend: GET /forgot-password (guest middleware)
    Backend->>Browser: Inertia render('auth/ForgotPassword')

    User->>Browser: Input email
    Browser->>Backend: POST /forgot-password (guest middleware)

    Note over Backend: Validasi: email=required|email

    Backend->>Backend: Password::sendResetLink(['email'])

    alt Success
        Backend->>Mailer: Kirim reset link email
        Backend->>Browser: back()->with('status') (generic "link sent")
        Browser-->>User: Pesan: link reset telah dikirim
    else Gagal
        Backend->>Browser: back()->withErrors(['email' => ...])
    end

    User->>Mailer: Buka email, klik link
    User->>Browser: GET /reset-password/{token}?email=...
    Browser->>Backend: GET /reset-password/{token} (guest middleware)
    Backend->>Browser: Inertia render('auth/ResetPassword', {email, token})

    User->>Browser: Input password baru + password_confirmation
    Browser->>Backend: POST /reset-password (guest middleware)

    Note over Backend: Validasi:<br/>token=required<br/>email=required|email<br/>password=required|confirmed|Password::defaults()

    Backend->>Backend: Password::reset(credentials, callback)
    Note over Backend: Callback: User::where('email', email)<br/>->update({password: Hash::make(password)})

    alt Success
        Backend->>Backend: Event: PasswordReset($user)
        Backend->>Browser: Redirect to login with status
    else Gagal (invalid token)
        Backend->>Browser: ValidationException('email','Invalid reset token')
    end
```

## Validasi

### Forgot Password

| Field | Rule |
|-------|------|
| `email` | required, email |

### Reset Password

| Field | Rule |
|-------|------|
| `token` | required |
| `email` | required, email |
| `password` | required, confirmed, Password::defaults() |

## Routes

| Method | URI | Controller | Middleware | Name |
|--------|-----|------------|------------|------|
| GET | `/forgot-password` | `PasswordResetLinkController@create` | `guest` | `password.request` |
| POST | `/forgot-password` | `PasswordResetLinkController@store` | `guest` | `password.email` |
| GET | `/reset-password/{token}` | `NewPasswordController@create` | `guest` | `password.reset` |
| POST | `/reset-password` | `NewPasswordController@store` | `guest` | `password.store` |

## Frontend

| File | Purpose |
|------|---------|
| `pages/auth/ForgotPassword.vue` | Email input form |
| `pages/auth/ResetPassword.vue` | Password + confirm + token hidden input |
