# User Management

CRUD pengguna dengan assignment role dan team, serta filter berbasis user role.

---

## Flow

```mermaid
sequenceDiagram
    actor Admin
    participant Browser
    participant Backend
    participant DB

    Note over Admin,DB: LIST
    Admin->>Browser: GET /user
    Browser->>Backend: GET /user (route.permission)
    Backend->>DB: User::with('roles.team')<br/>whereRelation('roles.team', filterByUserRole())<br/>->get()
    Backend->>Browser: Inertia render('user/User', {users: UserListResource})

    Note over Admin,DB: CREATE
    Admin->>Browser: GET /user/create
    Backend->>DB: Teams::filterByUserRole() + Roles::filterByUserRole()
    Backend->>Browser: Render UserForm

    Admin->>Browser: Input name, email, role, password, is_active
    Browser->>Backend: POST /user

    Note over Backend: Validasi UserStoreRequest:<br/>name=required|string|max:255<br/>email=required|email|unique:users,email<br/>role_id=required|numeric|exists:roles,id<br/>is_active=required|boolean<br/>password=required|string|min:8<br/>password_confirmation=confirmed:password

    Backend->>DB: User::create({name, email, hashedPassword, is_active, created_by=Auth::id()})
    Backend->>DB: $user->syncRoles(role_id)
    Backend->>Browser: Redirect route('user.index')

    Note over Admin,DB: EDIT
    Admin->>Browser: GET /user/{uuid}/edit
    Backend->>Backend: getByUuid(uuid): validasi UUID format, find by uuid
    Backend->>DB: Load teams + roles (both filtered)
    Backend->>Browser: Render UserForm (edit mode)

    Admin->>Browser: Update data
    Browser->>Backend: PUT /user/{uuid}

    Note over Backend: Validasi UserUpdateRequest:<br/>name=required|string|max:255<br/>email=required|email|unique:users,email (ignore self by uuid)<br/>role_id=required|numeric|exists:roles,id<br/>is_active=required|boolean<br/>password=nullable|string|min:8<br/>password_confirmation=confirmed:password

    Backend->>Backend: getByUuid(id) - MUST be valid UUID
    Backend->>DB: Update name, email, is_active
    Backend->>DB: Hash password ONLY if provided (!empty)
    Backend->>DB: $user->syncRoles(role_id)
    Backend->>Browser: Redirect route('user.index')

    Note over Admin,DB: DELETE
    Admin->>Browser: Delete (confirm dialog)
    Browser->>Backend: DELETE /user/{uuid}
    Backend->>Backend: getByUuid(id) - validasi UUID
    Backend->>DB: $user->delete()
    Backend->>Browser: Redirect
```

## Validasi

### UserStoreRequest

| Field | Rule |
|-------|------|
| `name` | required, string, max:255 |
| `email` | required, email, unique:users,email |
| `role_id` | required, numeric, exists:roles,id |
| `is_active` | required, boolean |
| `password` | required, string, min:8 |
| `password_confirmation` | confirmed:password |

### UserUpdateRequest

| Field | Rule |
|-------|------|
| `name` | required, string, max:255 |
| `email` | required, email, unique:users,email (ignore by uuid, withoutTrashed) |
| `role_id` | required, numeric, exists:roles,id |
| `is_active` | required, boolean |
| `password` | nullable, string, min:8 (hanya di-hash jika diisi) |
| `password_confirmation` | confirmed:password |
| `avatar` | nullable, image, mimes:jpeg,jpg,png,gif, max:2048 |

## UUID Validation

Setiap akses user menggunakan UUID sebagai identifier (bukan integer). Method `getByUuid()`:
1. `Guid::isValid($uuid)` — validasi format UUID via Ramsey UUID library
2. `User::whereUuid($uuid)->firstOrFail()` — find by uuid column

## Routes

| Method | URI | Controller | Model Binding |
|--------|-----|------------|---------------|
| GET | `/user` | `UserController@index` | — |
| GET | `/user/create` | `UserController@create` | — |
| POST | `/user` | `UserController@store` | — |
| GET | `/user/{user}/edit` | `UserController@edit` | UUID |
| PUT | `/user/{user}` | `UserController@update` | UUID |
| DELETE | `/user/{user}` | `UserController@destroy` | UUID |

## Frontend

| File | Purpose |
|------|---------|
| `pages/user/User.vue` | Halaman list |
| `pages/user/UserForm.vue` | Form create/edit: Name, Email, Team (Select filtering), Role (dynamic based on team), Active Toggle, Password + confirmation |
| `pages/user/partials/UserTable.vue` | DataTable: No, Team, Role, Name, Email, Status, Created Date, Created By, Actions |
