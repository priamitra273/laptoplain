# Team Management

CRUD tim/departemen dengan constraint Admin team tidak bisa dihapus.

---

## Flow

```mermaid
sequenceDiagram
    actor Admin
    participant Browser
    participant Backend
    participant DB

    Note over Admin,DB: LIST
    Admin->>Browser: GET /team
    Browser->>Backend: GET /team (route.permission)
    Backend->>DB: Team::select(uuid,name,created_at,updated_at)::filterByUserRole()::get()
    Backend->>Browser: Inertia render('team/Team', {teams})

    Note over Admin,DB: CREATE
    Admin->>Browser: Open drawer form -> input name
    Browser->>Backend: POST /team

    Note over Backend: Validasi TeamStoreRequest:<br/>name=required|string|max:255<br/>closure: reject "admin" (case-insensitive trimmed)

    Backend->>DB: Team::create({name})
    Backend->>Browser: Redirect route('team.index')

    Note over Admin,DB: UPDATE
    Admin->>Browser: Edit name
    Browser->>Backend: PUT /team/{team}
    Note over Backend: Model binding: Team $team (by UUID)
    Backend->>DB: $team->update({name})
    Backend->>Browser: Redirect route('team.index')

    Note over Admin,DB: DELETE
    Admin->>Browser: Delete (confirm dialog)
    Browser->>Backend: DELETE /team/{team}
    Backend->>Backend: abort_if($team->name == 'Admin', 404)
    Backend->>DB: $team->delete()
    Backend->>Browser: Redirect route('team.index')
```

## Validasi (TeamStoreRequest)

| Field | Rule |
|-------|------|
| `name` | required, string, max:255; closure: tidak boleh "admin" (case-insensitive, trimmed) |

## Routes

| Method | URI | Controller | Model Binding |
|--------|-----|------------|---------------|
| GET | `/team` | `TeamController@index` | — |
| POST | `/team` | `TeamController@store` | — |
| PUT | `/team/{team}` | `TeamController@update` | UUID |
| DELETE | `/team/{team}` | `TeamController@destroy` | UUID |

## Frontend

| File | Purpose |
|------|---------|
| `pages/team/Team.vue` | Halaman list |
| `pages/team/TeamListTable.vue` | DataTable dengan search, pagination, actions (edit/delete) |
| `pages/team/TeamForm.vue` | Drawer form (name input) |
