# Email Verification

Verifikasi alamat email pengguna setelah registrasi. Menggunakan signed URL untuk keamanan.

## Flow

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Backend
    participant Mailer

    User->>Browser: Akses halaman yang butuh verified
    Note over Browser: Middleware auth + verified

    Browser->>Backend: GET /verify-email (auth middleware)
    Backend->>Backend: Check: user sudah verified?
    alt Sudah verified
        Backend->>Browser: Redirect ke dashboard
    else Belum verified
        Backend->>Browser: Inertia render('auth/VerifyEmail', {status})
    end

    User->>Browser: Klik "Resend Verification Email"
    Browser->>Backend: POST /email/verification-notification (auth + throttle:6,1)

    Backend->>Backend: Check: user sudah verified?
    alt Sudah verified
        Backend->>Browser: Redirect
    else
        Backend->>Mailer: $user->sendEmailVerificationNotification()
        Backend->>Browser: back()->with('status','verification-link-sent')
    end

    User->>Mailer: Buka email, klik link
    User->>Browser: GET /verify-email/{id}/{hash}?expires=&signature=
    Browser->>Backend: GET (auth + signed + throttle:6,1)

    Note over Backend: Backend signed middleware verify:
    Backend->>Backend: Cek signature valid + not expired
    Backend->>Backend: check user->getKey() === id
    Backend->>Backend: hash_equals(sha1(user->email), hash)

    alt User already verified
        Backend->>Browser: Redirect dashboard?verified=1
    else
        Backend->>DB: user->markEmailAsVerified()
        Backend->>Backend: event(new Verified($user))
        Backend->>Browser: Redirect dashboard?verified=1
    end
```

## Validasi

**Signed route:** URL harus ditandatangani secara kriptografis (`URL::signedRoute()`) dengan parameter `id`, `hash`, `expires`, `signature`.

Laravel memverifikasi:
1. **Signature valid** — menggunakan APP_KEY
2. **Not expired** — expires timestamp
3. **id matches** — `user->getKey() === $id`
4. **hash matches** — `hash_equals(sha1($user->getEmailForVerification()), $hash)`

## Routes

| Method | URI | Controller | Middleware | Name |
|--------|-----|------------|------------|------|
| GET | `/verify-email` | `EmailVerificationPromptController` | `auth` | `verification.notice` |
| GET | `/verify-email/{id}/{hash}` | `VerifyEmailController` | `auth`, `signed`, `throttle:6,1` | `verification.verify` |
| POST | `/email/verification-notification` | `EmailVerificationNotificationController@store` | `auth`, `throttle:6,1` | `verification.send` |

## Frontend

| File | Purpose |
|------|---------|
| `pages/auth/VerifyEmail.vue` | Prompt + resend button + status message |
