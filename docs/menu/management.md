# Menu Management

Manajemen menu navigasi sidebar yang dinamis dengan struktur hierarkis (parent-child).

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
    Admin->>Browser: GET /menu
    Browser->>Backend: GET /menu (route.permission)
    Backend->>DB: Menu::orderByRaw('parent_id NULLS FIRST')<br/>orderBy('sequence_number')->get()
    Backend->>DB: Parent menus: whereNull('parent_id')
    Backend->>MenuService: getAvailableRoutes(existingRouteNames)
    Note over MenuService: Scan semua GET route dengan auth middleware<br/>Filter .index atau top-level route<br/>Exclude routes yang sudah dipakai menu
    Backend->>Browser: Inertia render('menu/Menu', {menu, parent_menu, available_routes})

    Note over Admin,DB: CREATE
    Admin->>Browser: Open drawer -> input label, parent, icon, route, sequence
    Browser->>Backend: POST /menu

    Note over Backend: Validasi MenuStoreRequest:<br/>label=required|string|max:255<br/>parent_uuid=nullable|uuid|exists:menus,uuid<br/>icon=required|string|max:255<br/>route_name=nullable|string|max:255<br/>  Rule::requiredIf parent_uuid not empty<br/>  Rule::in(available_routes)<br/>  Rule::unique(menus,route_name)->withoutTrashed()<br/>sequence_number=nullable|numeric<br/>is_active=required|boolean

    Backend->>MenuService: store(validated)
    Note over MenuService: DB::transaction
    Note over MenuService: Resolve parent_id from parent_uuid
    Note over MenuService: Increment sequence_number for rows >= new sequence (same parent)
    MenuService->>DB: Menu::create()
    Note over MenuService: Generate URI slug dari route name atau label
    Note over MenuService: Create 4 permissions: {uri}.create, .read, .update, .delete
    MenuService->>DB: Assign permissions to menu
    Backend->>Browser: Redirect route('menu.index')

    Note over Admin,DB: UPDATE
    Admin->>Browser: Edit menu
    Browser->>Backend: PUT /menu/{menu}

    Note over Backend: Validasi MenuUpdateRequest:<br/>Sama seperti store tapi route_name unique ignore self

    Backend->>MenuService: update(validated, menu)
    Note over MenuService: Decrement sequence above old position
    Note over MenuService: Increment sequence at/above new position (exclude self)
    MenuService->>DB: menu->update()
    Backend->>Browser: Redirect

    Note over Admin,DB: DELETE
    Admin->>Browser: Delete menu
    Browser->>Backend: DELETE /menu/{menu}
    Backend->>DB: $menu->delete()
    Backend->>Browser: Redirect
```

## Validasi

### MenuStoreRequest

| Field | Rule |
|-------|------|
| `label` | required, string, max:255 |
| `parent_uuid` | nullable, uuid:4, exists:menus,uuid (resolves to parent_id) |
| `icon` | required, string, max:255 |
| `route_name` | nullable, string, max:255; requiredIf parent_uuid not empty; in(available_routes); unique:menus,route_name (withoutTrashed) |
| `sequence_number` | nullable, numeric |
| `is_active` | required, boolean |

### MenuUpdateRequest

Sama, tapi `route_name` unique ignore self (menu->id).

**passedValidation:** merge `parent_id` dari `Menu::findByUuid(parent_uuid)`.

## Menu Permission Auto-Creation

Saat create menu, `MenuService::store()` otomatis membuat 4 permission:

| Permission | Format |
|------------|--------|
| Create | `{uri}.create` |
| Read | `{uri}.read` |
| Update | `{uri}.update` |
| Delete | `{uri}.delete` |

URI di-generate dari route name (jika ada) atau slug dari label.

## Dynamic Sidebar

Menu yang dibuat via halaman ini ditampilkan di sidebar (`AppMenu.vue` / `AppSidebar.vue`). Menu bersifat:
- Hierarkis (parent_id NULL untuk top-level, non-NULL untuk submenu)
- Diurutkan berdasarkan `sequence_number`
- Dapat diaktifkan/dinonaktifkan dengan toggle `is_active`

## Routes

| Method | URI | Controller | Model Binding |
|--------|-----|------------|---------------|
| GET | `/menu` | `MenuController@index` | — |
| POST | `/menu` | `MenuController@store` | — |
| PUT | `/menu/{menu}` | `MenuController@update` | UUID |
| DELETE | `/menu/{menu}` | `MenuController@destroy` | UUID |

## Frontend

| File | Purpose |
|------|---------|
| `pages/menu/Menu.vue` | DataTable: Label, Parent, Icon (rendered via Icon.vue), Route Name, Sequence, Status (active/nonactive), Actions |
| `pages/menu/MenuForm.vue` | Drawer form: Label, Parent (Select), Icon (Select), Route (Select from available), Sequence, Active toggle |
