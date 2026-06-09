# Settings

Pengaturan pengguna: Profile (name, email, avatar), Password, Appearance (dark/light mode).

---

## 1. Profile

```mermaid
sequenceDiagram
    actor User
    participant Browser
    participant Backend
    participant DB

    Note over User,DB: VIEW
    User->>Browser: Buka /settings/profile
    Browser->>Backend: GET /settings/profile
    Backend->>Browser: Inertia render('settings/Profile', {mustVerifyEmail, status})

    Note over User,DB: UPDATE
    User->>Browser: Update name, email, atau upload avatar
    Browser->>Backend: PATCH /settings/profile

    Note over Backend: Validasi ProfileUpdateRequest:<br/>name=required|string|max:255<br/>email=required|string|lowercase|email|max:255|unique:users (ignore self)

    Backend->>DB: $user->fill(validated)
    alt Ada file avatar
        Backend->>DB: clearMediaCollection('avatar')
        Backend->>DB: addMediaFromRequest('avatar')->toMediaCollection('avatar')
    end
    alt Email berubah
        Backend->>DB: email_verified_at = null
    end
    Backend->>DB: $user->save()
    Backend->>Browser: Redirect to_route('profile.edit')->with('status','profile-updated')

    Note over User,DB: DELETE AVATAR
    User->>Browser: Klik remove avatar
    Browser->>Backend: DELETE /settings/profile/avatar
    Backend->>DB: clearMediaCollection('avatar')
    Backend->>Browser: back()->with('status','avatar-removed')

    Note over User,DB: DELETE ACCOUNT
    User->>Browser: Klik "Delete Account" + masukkan password
    Browser->>Backend: DELETE /settings/profile

    Note over Backend: Validasi password=required|current_password

    Backend->>DB: clearMediaCollection('avatar') (Spatie auto-delete)
    Backend->>Backend: Auth::logout()
    Backend->>DB: $user->delete()
    Backend->>Backend: session()->invalidate() + regenerateToken()
    Backend->>Browser: Redirect ke /
```

## 2. Password

```mermaid
sequenceDiagram
    User->>Browser: Buka /settings/password
    Browser->>Backend: GET /settings/password
    Backend->>Browser: Inertia render('settings/Password', {mustVerifyEmail, status})

    User->>Browser: Input current password + new password + confirmation
    Browser->>Backend: PUT /settings/password

    Note over Backend: Validasi:<br/>current_password=required|current_password<br/>password=required|Password::defaults()|confirmed

    Backend->>DB: $user->update({password: Hash::make(validated.password)})
    Backend->>Browser: Redirect back
```

## 3. Appearance

```mermaid
sequenceDiagram
    User->>Browser: Buka /settings/appearance
    Browser->>Backend: GET /settings/appearance
    Backend->>Browser: Inertia render('settings/Appearance')
    Browser-->>User: Dark / Light / System mode toggle
```

## Validasi

### ProfileUpdateRequest

| Field | Rule |
|-------|------|
| `name` | required, string, max:255 |
| `email` | required, string, lowercase, email, max:255, unique:users (ignore self by id) |

### Password Update

| Field | Rule |
|-------|------|
| `current_password` | required, current_password |
| `password` | required, Password::defaults(), confirmed |

### Delete Account

| Field | Rule |
|-------|------|
| `password` | required, current_password |

## Routes

| Method | URI | Controller | Name |
|--------|-----|------------|------|
| GET | `/settings` | Redirect | `/settings/profile` |
| GET | `/settings/profile` | `ProfileController@edit` | `profile.edit` |
| PATCH | `/settings/profile` | `ProfileController@update` | `profile.update` |
| DELETE | `/settings/profile` | `ProfileController@destroy` | `profile.destroy` |
| DELETE | `/settings/profile/avatar` | `ProfileController@destroyAvatar` | `profile.avatar.destroy` |
| GET | `/settings/password` | `PasswordController@edit` | `password.edit` |
| PUT | `/settings/password` | `PasswordController@update` | `password.update` |
| GET | `/settings/appearance` | Inline Inertia render | `appearance` |

## Frontend

| File | Purpose |
|------|---------|
| `pages/settings/Profile.vue` | Edit name, email, avatar upload (FileUpload + Cropper.js), delete account form |
| `pages/settings/Password.vue` | Current + new password + confirm |
| `pages/settings/Appearance.vue` | Dark/Light/System toggle |
| `layouts/settings/Layout.vue` | Settings sub-layout dengan side nav |
