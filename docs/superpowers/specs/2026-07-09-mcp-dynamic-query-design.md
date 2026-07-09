# MCP Dynamic Query — Design Spec

- **Tanggal**: 2026-07-09
- **Status**: Disetujui (siap masuk tahap writing-plans)
- **Konteks**: Menambahkan kemampuan query dinamis (read-only) ke model domain lewat MCP server `ProjectManagementServer` yang sudah ada (`routes/ai.php`, dilindungi `auth:api` / Passport).

## 1. Tujuan

Memberi AI kemampuan menjalankan **query SELECT dinamis** ke model terkait Task & Project, **dengan dukungan join**, tanpa membuka celah keamanan:

- Hanya operasi baca (SELECT). Tidak ada jalur ke INSERT/UPDATE/DELETE.
- Mendukung **join tabel eksplisit** (hasil flat, untuk report/agregasi) **dan** pemuatan **relasi Eloquent** (`with`, hasil nested).
- **Scoping per-baris mengikuti visibilitas user** — persis seperti `Project::scopeVisibleFor`.
- **Sqids** dipakai untuk id di input & output, konsisten dengan tool MCP lain.

## 2. Keputusan yang sudah dikonfirmasi

| Topik | Keputusan |
|---|---|
| Scope data | Ikuti visibilitas user (member project; role `super-admin`/`watcher` lihat semua). |
| Antarmuka query | Query builder JSON terstruktur (bukan SQL mentah). Semua value di-bind. |
| Join | Dukung **keduanya**: join tabel eksplisit + relasi Eloquent (`with`). |
| Format ID | Encode/decode Sqids (simetris input/output). |
| Tool introspeksi | Ya — `list-queryable-models` disetujui. |
| Comments/notifications | Dikecualikan dari whitelist awal (masih dipertimbangkan user; mudah ditambah nanti). |

## 3. Arsitektur

Prinsip inti: **allowlist deklaratif** — apa pun yang tidak dideklarasikan di registry ditolak.

| Komponen | Peran |
|---|---|
| `config/mcp_query.php` | Registry / whitelist. Satu sumber kebenaran: model yang boleh di-query, kolom, relasi (+ target), tipe scope, kolom id. Mudah di-audit. |
| `App\Services\DynamicQuery\DynamicQueryService` | Menerima input terstruktur + user; membangun & menjalankan query; menerapkan seluruh aturan keamanan; mengembalikan array hasil. |
| `App\Mcp\Tools\QueryDataTool` (nama MCP `query-data`) | Tool utama. `#[IsReadOnly]` + `#[IsIdempotent]`. Mendefinisikan schema input, mendelegasikan ke service. |
| `App\Mcp\Tools\QueryableSchemaTool` (nama MCP `list-queryable-models`) | Tool introspeksi. Mengembalikan katalog registry agar AI menyusun query yang valid. `#[IsReadOnly]`. |
| `ProjectManagementServer` | Mendaftarkan kedua tool baru pada array `$tools`. |

Namespace/berkas mengikuti konvensi `app/Mcp/Tools/*` yang sudah ada.

## 4. Registry & klasifikasi scope

Setiap model punya entri registry berisi: `table`, `model` (kelas Eloquent), `columns` (allowlist select/filter/join), `relations` (alias `with` yang diizinkan → alias model target), `joinable` (target join yang diizinkan), `scope` (`project` | `global`), dan metadata scope (kolom FK / subquery).

### 4.1 Project-scoped (baris dibatasi ke project visible)

| Alias | Tabel | Ekspresi scope |
|---|---|---|
| `project` | `projects` | `projects.id IN (visibleProjectIds)` |
| `task` | `tasks` | `tasks.project_id IN (visibleProjectIds)` |
| `project_sprint` | `project_sprints` | `project_sprints.project_id IN (visibleProjectIds)` |
| `project_member` | `project_members` | `project_members.project_id IN (visibleProjectIds)` |
| `sprint_task` | `sprint_task` | `sprint_task.sprint_id IN (SELECT id FROM project_sprints WHERE project_id IN (visibleProjectIds))` |
| `task_user` | `task_users` | `task_users.task_id IN (SELECT id FROM tasks WHERE project_id IN (visibleProjectIds))` |

### 4.2 Global / reference (lookup non-sensitif, tanpa scope baris)

`user` (kolom dibatasi: `id, name, email, is_active, created_at, updated_at` — **tanpa** `password`, `remember_token`, `uuid`), `ms_project_status`, `ms_project_priority`, `ms_task_status`, `ms_task_priority`, `ms_task_type`, `task_category`, `ms_sprint_status`, `ms_project_role`, `tag`.

### 4.3 Logika penentuan visibilitas (meniru `Project::scopeVisibleFor`)

```
allowedRoles = ['super-admin-admin', 'watcher-admin']  // sesuai scopeVisibleFor
if user.getRoleNames() ∩ allowedRoles  → seesAll = true  (lewati semua constraint project-scope)
else → visibleProjectIds = project_members milik user (via Project::visibleFor(user)->pluck('id'))
```

Constraint disuntikkan ke **setiap** tabel project-scoped yang muncul: base, tabel yang di-join, **dan** di dalam closure `with` (bila relasi menuju tabel project-scoped). Jadi join/relasi tidak bisa membocorkan baris lintas-project.

## 5. Bentuk input (DSL terstruktur)

```jsonc
{
  "model": "task",                       // wajib, alias registry
  "select": ["id", "title", "progress", "status.name"],
  "distinct": false,
  "joins": [
    { "type": "left", "model": "ms_task_status",
      "on": [{ "left": "tasks.status_id", "operator": "=", "right": "ms_task_statuses.id" }] }
  ],
  "with": ["status", "users"],           // alternatif join (nested); tidak boleh dipakai bareng aggregates/group_by
  "filters": [
    { "column": "tasks.project_id", "operator": "=", "value": "<sqid>" },
    { "boolean": "and", "column": "progress", "operator": ">=", "value": 50 }
  ],
  "group_by": ["status.name"],
  "aggregates": [{ "function": "count", "column": "tasks.id", "alias": "total" }],
  "order_by": [{ "column": "progress", "direction": "desc" }],
  "limit": 50,                           // default 50, hard-max 200
  "offset": 0,
  "with_trashed": false                  // default false
}
```

- Operator filter yang diizinkan: `=, !=, >, >=, <, <=, like, in, not in, is null, is not null`.
- Fungsi agregasi yang diizinkan: `count, sum, avg, min, max`.
- `with` (nested) dan `aggregates`/`group_by` (flat) **saling eksklusif** — dipakai bersamaan → ditolak dengan pesan jelas.
- Referensi kolom boleh `col` atau `table.col` / `relation.col`; semuanya divalidasi ke registry.

Catatan implementasi: schema input MCP memakai `Illuminate\Contracts\JsonSchema\JsonSchema` dengan objek/array bersarang (seperti pola `outputSchema` pada tool yang ada). Bila klien MCP kesulitan dengan input bersarang dalam, fallback: terima satu parameter `query` bertipe string JSON lalu validasi via `Validator`. Keputusan final diverifikasi saat implementasi (cek `search-docs`).

## 6. Enforcement keamanan

1. **SELECT-only by construction** — service hanya memanggil builder select/where/join/orderBy/limit. Tidak ada raw SQL, tidak ada jalur mutasi.
2. **Validasi referensi** — `model`, kolom, relasi, target join, kolom `on`, kolom filter/order/group semuanya dicek ke registry; tak dikenal → `Response::error` dengan pesan actionable.
3. **Scoping per-baris** — sesuai bagian 4.
4. **Sqids simetris** — kolom bernama `id` atau berakhiran `_id`: input di-decode ke int (mengikuti pola `rec_decode_ids_in_list`), output di-encode via `rec_encode_ids_in_list`.
5. **Soft-delete** — default `whereNull(deleted_at)` untuk tabel yang punya kolom itu; di-include hanya bila `with_trashed: true`.
6. **Limit** — default 50, hard-max 200; melebihi → ditolak.

## 7. Output

Seragam untuk kedua mode:

```php
return Response::json(['data' => Sqids::rec_encode_ids_in_list($rows)]);
```

`rec_encode_ids_in_list` meng-encode id baik pada hasil nested (`with`) maupun flat (join/aggregate) berdasarkan nama key final — sehingga AI wajib memberi alias unik pada kolom id saat join agar tak bentrok.

## 8. Tool introspeksi `list-queryable-models`

Mengembalikan katalog dari registry: untuk tiap alias model → daftar kolom yang boleh, relasi yang boleh (+ alias target), tabel joinable, dan tipe scope. Tidak butuh parameter. AI dianjurkan memanggil ini lebih dulu.

## 9. Rencana testing (Pest feature)

Skenario minimum:

- SELECT-only: input yang mencoba menyisipkan sesuatu selain select ditolak / tak mungkin terjadi.
- Model / kolom / relasi tak dikenal → error.
- **Scoping**: user A tak dapat melihat task milik project user B; role `super-admin`/`watcher` melihat semua.
- Join tabel eksplisit menghasilkan baris flat yang benar.
- `with` menghasilkan struktur nested yang benar.
- Agregasi + `group_by` benar.
- Round-trip Sqids (decode input, encode output).
- `limit` default & hard-max dipaksa.
- Soft-delete dikecualikan default; muncul saat `with_trashed`.
- `with` + `aggregates` bersamaan ditolak.

> ⚠️ **Keamanan DB test**: test memakai `RefreshDatabase` (drop + migrate ulang). Sebelum menjalankan, WAJIB konfirmasi ulang target adalah `.env.testing` / database test disposable, sesuai aturan proyek di `CLAUDE.md`.

## 10. Batasan sengaja (YAGNI)

`comments`, `comment_reactions`, `notifications`, dan pivot `taggables` **tidak** masuk whitelist awal (polymorphic/peripheral, scoping sulit dibuktikan aman). Tag tetap terjangkau lewat relasi `task.tags` yang sudah ter-scope. Comments akan ditambah bila user memutuskan membutuhkannya.

## 11. Berkas yang akan dibuat/diubah

- **Baru** `config/mcp_query.php`
- **Baru** `app/Services/DynamicQuery/DynamicQueryService.php` (+ kelas pendukung bila perlu, mis. `QueryScopeResolver`)
- **Baru** `app/Mcp/Tools/QueryDataTool.php`
- **Baru** `app/Mcp/Tools/QueryableSchemaTool.php`
- **Ubah** `app/Mcp/Servers/ProjectManagementServer.php` (registrasi 2 tool)
- **Baru** test Pest di `tests/Feature/Mcp/` (mengikuti konvensi test yang ada)
