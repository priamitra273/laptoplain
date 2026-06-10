# Role Management

Manajemen role/peran dengan pengaturan permission berbasis menu (CRUD per menu item).

---

## Flow

```mermaid
sequenceDiagram
    actor Admin
    participant Browser
    participant Backend
    participant MenuService
    participant DB

    Note over Admin,DB: LIST
    Admin->>Browser: GET /role
    Browser->>Backend: GET /role (route.permission)
    Backend->>Backend: Is super admin?
    alt Not super admin
        Backend->>DB: Role::whereRelation('team','name','!=','Admin')->get()
    else Super admin
        Backend->>DB: Role::all()
    end
    Backend->>Browser: Inertia render('role/Role', {roles: RoleListResource})

    Note over Admin,DB: CREATE
    Admin->>Browser: GET /role/create
    Browser->>Backend: Load teams (filterByUserRole), menus, permissions
    Backend->>MenuService: getMenuPermissions()
    Note over MenuService: Joins menus → model_has_permissions → permissions<br/>Returns structure: {menu_uuid, menu_label, permission_name, route_name}
    Backend->>Browser: Render RoleForm

    Admin->>Browser: Pilih team + nama + checklist permissions
    Browser->>Backend: POST /role

    Note over Backend: Validasi RoleStoreRequest:<br/>label=required|string|max:255<br/>team_uuid=required|uuid|exists:teams,uuid (not 'Admin' for non-super-admin)<br/>is_active=required|boolean<br/>permissions=required|array|min:1<br/>permissions.*=required|string|exists:permissions,name

    Backend->>DB: Team::findByUuid(team_uuid)
    Backend->>DB: Role::create({name: str(label-team-name)->slug(), guard_name:'web', label, team_id, is_active})
    Backend->>DB: $role->syncPermissions(request->permissions)
    Backend->>Browser: Redirect route('role.index')

    Note over Admin,DB: EDIT
    Admin->>Browser: GET /role/{role}/edit
    Browser->>Backend: Load role (RoleResource) + teams + menus + permissions
    Backend->>Browser: Render RoleForm (edit mode)

    Admin->>Browser: Update data + permissions
    Browser->>Backend: PUT /role/{role}
    Backend->>DB: Update: name (slugged), label, team_id, is_active
    Backend->>DB: $role->syncPermissions(permissions)
    Backend->>Browser: Redirect route('role.index')

    Note over Admin,DB: DELETE
    Admin->>Browser: Delete role
    Browser->>Backend: DELETE /role/{role}
    Backend->>DB: $role->delete()
    Backend->>Browser: Redirect
```

## Validasi (RoleStoreRequest)

| Field | Rule |
|-------|------|
| `label` | required, string, max:255 |
| `team_uuid` | required, uuid, exists:teams,uuid (filter: not 'Admin' for non-super-admin) |
| `is_active` | required, boolean |
| `permissions` | required, array, min:1 |
| `permissions.*` | required, string, exists:permissions,name |

## Role Naming Convention

Role name di-generate otomatis: `str(label-teamname)->slug()`

Contoh: label="Developer", team="Engineering" → name="developer-engineering"

## Permission Structure

Permission format: `{uri}.{action}` (e.g., `project.create`, `project.read`, `project.update`, `project.delete`)

Setiap menu menghasilkan 4 permissions: `create`, `read`, `update`, `delete`.

## Routes

| Method | URI | Controller |
|--------|-----|------------|
| GET | `/role` | `RoleController@index` |
| GET | `/role/create` | `RoleController@create` |
| POST | `/role` | `RoleController@store` |
| GET | `/role/{role}/edit` | `RoleController@edit` |
| PUT | `/role/{role}` | `RoleController@update` |
| DELETE | `/role/{role}` | `RoleController@destroy` |

## Frontend

| File | Purpose |
|------|---------|
| `pages/role/Role.vue` | Halaman list (wrapper, render RoleListTable) |
| `pages/role/partials/RoleListTable.vue` | DataTable role dengan actions |
| `pages/role/RoleForm.vue` | Form create/edit: Team Select, Role Name, Active ToggleSwitch, permission matrix |
| `pages/role/partials/SetupPermission.vue` | Hierarchical checkbox tree per menu item |
