# Desain: Pemuatan Task Rekursif via Raw PostgreSQL CTE + Relasi di Aplikasi → Laravel Data

- **Tanggal:** 2026-06-12
- **Branch:** `refactor/project-detail`
- **Status:** Disetujui (menunggu review spec sebelum implementasi)

## 1. Latar Belakang & Masalah

Halaman `project/Detail` memuat tiga kumpulan task lewat Eloquent eager loading di `ProjectRepository`:

- `tasks` — pohon task rekursif project (via `Project::tasks()->withRecursive()`).
- `sprints.tasks` — task per sprint aktif (`getActiveSprints`).
- `backlog` — task tanpa sprint (`getBacklogTasks`).

Bottleneck utama ada di [`Task::scopeWithRecursive()`](../../../app/Models/Task.php) yang memuat relasi `subTaskRecursive` secara rekursif lewat Eloquent. Setiap level kedalaman memicu **satu query terpisah** (N-per-level), plus query relasi di tiap level.

**Tujuan:** mengganti pemuatan **pohon task** dengan **raw PostgreSQL recursive CTE** sehingga baris pohon dimuat dalam **1 query** (bebas N-per-level), tanpa rekursi ORM. **Relasi (status, priority, type, category, users, tags, creator, media) tidak dirakit di SQL** — itu boros (subquery per-baris). Relasi di-load & dipetakan **di aplikasi** secara batch (jumlah query tetap, tak tergantung kedalaman). Hasil dikembalikan sebagai objek **Laravel Data** (`ProjectTaskData`) dari repository, tipe TypeScript-nya digenerate, lalu frontend dimigrasikan ke tipe baru tersebut.

## 2. Keputusan yang Disepakati

| Topik | Keputusan |
|---|---|
| Cakupan | `tasks` + `sprints.tasks` + `backlog` semua memetakan ke `ProjectTaskData`. |
| Raw SQL | **Hanya pohon `tasks`** (rekursif) yang pakai raw recursive CTE. `sprints.tasks` & `backlog` non-rekursif → query Eloquent standar (lihat §3.3). |
| Perakitan relasi | **Semua relasi di-load di aplikasi** via Eloquent eager-load (bukan JSON subquery di SQL). |
| Mekanisme load relasi | `Task::hydrate($rows)` (0 query) → `->load([...relasi flat...])` (1 query batch per relasi) → `ProjectTaskData::fromModel()`. |
| Field komputasi (`is_overdue`, `completed_at`) | Dihitung di **PHP** (di dalam `ProjectTaskData::fromModel`). |
| `is_assigned` / `is_created_by_me` | Opsional, **tidak diisi** untuk prop ini. |
| URL media/avatar (Spatie) | Otomatis via Eloquent: `avatar_url` (accessor User) & `MediaData::fromModel()` (`getFullUrl()`). Tidak ada pembentukan URL manual. |
| Bentuk kembalian repository | Objek **Laravel Data** (`DataCollection<ProjectTaskData>`). |
| Tipe TypeScript | **Generate** dari Data class (`#[TypeScript]`) → `App.Data.Task.ProjectTaskData`; **migrasi** frontend & pensiunkan interface `Task` hand-written. |
| Lingkup spec ini | Backend + migrasi frontend dalam **satu** spec. |

> Catatan cakupan: keputusan awal "raw query untuk tasks + sprints + backlog" diambil saat relasi direncanakan dibangun di SQL. Karena relasi kini dirakit di aplikasi, raw SQL hanya memberi manfaat pada bagian **rekursif** (pohon). Jika tetap ingin raw SQL untuk sprint/backlog, perlu dikonfirmasi.

### Konteks teknis terverifikasi

- DB = **PostgreSQL** (`DB_CONNECTION=pgsql`) → `WITH RECURSIVE` tersedia.
- Aturan `Sqids::rec_encode_ids_in_list` ([`SqidsService.php`](../../../app/Services/SqidsService.php)): hanya key `id` dan berakhiran `_id` (regex `/_id$/`) di-encode menjadi string saat numerik; rekursif ke array nested. `created_by`/`updated_by`/`deleted_by` **tidak** di-encode → tetap numerik. Encoding dilakukan di controller setelah `->toArray()`.
- Data class master sudah cocok dengan interface & bisa di-reuse untuk relasi: `TaskStatusData` (punya `score`), `TaskPriorityData`, `TaskTypeData`, `TagData`, `TaskCategoryData`, `UserData`, `MediaData`.
- `Task::hydrate(array $rows)` membuat model dari baris mentah **tanpa query**; cast & `$appends` tetap berlaku saat serialisasi. SoftDeletes global scope tidak ikut → filter `deleted_at` ditangani di SQL CTE.
- `pivot` task-user **tidak dipakai** frontend → relasi `users` cukup `UserData[]` (dengan `avatar_url`, tanpa pivot).

## 3. Arsitektur

### 3.1 Komponen baru / berubah (backend)

| Komponen | Tipe | Tanggung jawab |
|---|---|---|
| `ProjectRepository::getTaskTree(int $projectId)` | method baru | Jalankan raw recursive CTE (baris task + `depth`) → `Task::hydrate` → `->load([...relasi flat...])` → rakit pohon by `parent_id` → return `DataCollection<ProjectTaskData>`. |
| `App\Data\Task\ProjectTaskData` | Data class baru (`#[TypeScript]`) | Representasi penuh sesuai interface `Task`. Factory `fromModel(Task $task, DataCollection $children)`. Menghitung `is_overdue`/`completed_at`. |
| `ProjectRepository::findWithRelationsForShow` | diubah | Hapus blok eager-load `tasks` (`withRecursive`); tetap memuat project + relasi non-task (members dll). |
| `ProjectRepository::getActiveSprints` | diubah | Tasknya dipetakan ke `ProjectTaskData::fromModel` (tetap query Eloquent datar seperti sekarang, dengan `->load` relasi yang sama). |
| `ProjectRepository::getBacklogTasks` | diubah | Return `DataCollection<ProjectTaskData>` (Eloquent datar + `->load` relasi). |
| `ProjectService::getShowData` | diubah | Panggil method repo baru; `->toArray()` per prop (pola eksisting). Hapus `formatProjectTasks` (logika pindah ke `ProjectTaskData`). |

> Komponen yang **dibatalkan** dari desain sebelumnya: `TaskRowSelect` (pembangun JSON relasi di SQL) dan `MediaUrlResolver` (Media in-memory) — tidak diperlukan karena relasi & URL ditangani Eloquent/DTO eksisting.

### 3.2 Aliran data

```
ProjectController::show(encoded)
  └─ ProjectService::getShowData(encoded)
       ├─ repo.findWithRelationsForShow()  → project + members + relasi non-task
       ├─ repo.getTaskTree(projectId)      → DataCollection<ProjectTaskData>
       │     1) DB::select(CTE)            → baris task pohon            [1 query]
       │     2) Task::hydrate(rows)        → model                       [0 query]
       │     3) ->load([relasi flat])      → batch per relasi            [N relasi query, tetap]
       │     4) rakit pohon + fromModel    → DataCollection              [0 query]
       ├─ repo.getActiveSprints(projectId) → sprint + tasks ProjectTaskData (Eloquent)
       ├─ repo.getBacklogTasks(projectId)  → DataCollection<ProjectTaskData> (Eloquent)
       └─ tiap prop ->toArray()
  └─ Sqids::rec_encode_ids_in_list(data)   → encode id & *_id menjadi string
  └─ Inertia::render('project/Detail', data)
```

Profil query pohon: **1 query rekursif** (baris) + **sejumlah tetap** query batch relasi (mis. ~9: status, priority, type, category, users, users.media, tags, creator(+media), media) — **tidak** bergantung kedalaman/jumlah task. Ini menggantikan N-per-level yang tak terbatas.

### 3.3 Sprint & backlog (non-rekursif)

`getActiveSprints`/`getBacklogTasks` tetap query Eloquent datar (filter `parent_id IS NULL OR parent.category = 'Epic'` seperti sekarang), namun:

- relasi di-`load` dengan daftar yang sama seperti pohon (lihat §4.2),
- hasil dipetakan ke `ProjectTaskData::fromModel(task, children: empty)` (sprint/backlog memang tanpa nesting).

## 4. Implementasi pemuatan pohon

### 4.1 Raw recursive CTE (baris task saja)

```sql
WITH RECURSIVE task_tree AS (
    SELECT t.id, t.owned_id, t.parent_id, t.status_id, t.priority_id, t.type_id,
           t.task_category_id, t.created_by, t.updated_by, t.deleted_by,
           t.emoji, t.title, t.description, t.start_date, t.due_date,
           t.progress, t.story_points, t.sequence_number, t.is_archived,
           t.project_id, t.created_at, t.updated_at, t.deleted_at, 0 AS depth
    FROM tasks t
    WHERE t.project_id = :projectId
      AND t.parent_id IS NULL
      AND t.deleted_at IS NULL
    UNION ALL
    SELECT c.id, c.owned_id, c.parent_id, c.status_id, c.priority_id, c.type_id,
           c.task_category_id, c.created_by, c.updated_by, c.deleted_by,
           c.emoji, c.title, c.description, c.start_date, c.due_date,
           c.progress, c.story_points, c.sequence_number, c.is_archived,
           c.project_id, c.created_at, c.updated_at, c.deleted_at, tt.depth + 1
    FROM tasks c
    JOIN task_tree tt ON c.parent_id = tt.id
    WHERE c.deleted_at IS NULL
)
SELECT * FROM task_tree
ORDER BY depth, sequence_number, id;
```

Tidak ada JSON/relasi di SQL — murni baris task. `deleted_at IS NULL` direplikasi di anchor & recursive (karena hydrate melewati SoftDeletes global scope).

### 4.2 Hydrate + eager-load relasi (di aplikasi)

```php
$rows  = DB::select($sql, ['projectId' => $projectId]);   // 1 query
$tasks = Task::hydrate($rows);                             // 0 query

$tasks->load([
    'status:id,name,severity,score',
    'priority:id,name,severity',
    'type:id,name,severity',
    'category:id,name,icon,severity',
    'users:id,name,email', 'users.media',
    'tags:id,name,severity',
    'creator:id,name,email', 'creator.media',
    'media' => fn ($q) => $q->where('collection_name', 'attachments'),
]);                                                        // batch, jumlah tetap
```

`users()` (relasi) sudah membawa `wherePivotNull('deleted_at')` → soft-delete pivot otomatis terhormati. `avatar_url` & `MediaData` jalan otomatis dari media yang di-load.

### 4.3 Perakitan pohon (PHP, O(n))

1. Bangun map `parent_id → Task[]` dari koleksi hasil (sudah terurut `depth, sequence_number, id`).
2. Susun `ProjectTaskData` **bottom-up**: anak menjadi `DataCollection<ProjectTaskData>` sebelum induk; set `sub_task` = `sub_task_recursive` = koleksi anak yang sama.
3. Return node root (`parent_id IS NULL`).

## 5. `ProjectTaskData` (Laravel Data)

```php
#[TypeScript]
class ProjectTaskData extends Data
{
    public function __construct(
        #[TypeScriptType('string')]      public int $id,
        #[TypeScriptType('string|null')] public ?int $owned_id,
        #[TypeScriptType('string|null')] public ?int $parent_id,
        #[TypeScriptType('string')]      public int $status_id,
        #[TypeScriptType('string')]      public int $priority_id,
        #[TypeScriptType('string')]      public int $type_id,
        public ?int $created_by,           // TIDAK di-encode → number|null
        public ?int $updated_by,
        public ?int $deleted_by,
        public ?string $emoji,
        public string $title,
        public ?string $description,
        public ?string $start_date,
        public ?string $due_date,
        public float $progress,
        public ?int $sequence_number,
        public bool $is_archived,
        public ?string $created_at,
        public ?string $updated_at,
        public ?string $deleted_at,
        public ?string $completed_at,      // komputasi
        public bool $is_overdue,           // komputasi
        #[TypeScriptType('string')] public int $project_id,
        public ?TaskStatusData $status,
        public ?TaskPriorityData $priority,
        public ?TaskTypeData $type,
        public ?TaskCategoryData $category,
        /** @var DataCollection<int, UserData> */
        public DataCollection $users,      // tanpa pivot, dengan avatar
        /** @var DataCollection<int, TagData> */
        public DataCollection $tags,
        public ?UserData $creator,
        public ?int $story_points,         // kolom tasks, ikut output
        /** @var DataCollection<int, MediaData> */
        public DataCollection $media,
        /** @var DataCollection<int, ProjectTaskData> */
        public DataCollection $sub_task,
        /** @var DataCollection<int, ProjectTaskData> */
        public DataCollection $sub_task_recursive,
        // opsional, tidak diisi prop ini (tetap ada demi kompat tipe)
        public ?bool $is_assigned = null,
        public ?bool $is_created_by_me = null,
    ) {}

    public static function fromModel(Task $task, DataCollection $children): self
    {
        // map relasi via relationLoaded() (pola seperti TaskData eksisting),
        // hitung is_overdue/completed_at, set sub_task = sub_task_recursive = $children
    }
}
```

### Logika field komputasi (dipindah dari `formatProjectTasks`)

```
isCompleted  = upper(status.name) ∈ {COMPLETED, FINISHED}
completed_at = isCompleted ? updated_at(JSON) : null
is_overdue   = isCompleted ? updated_at > due_date.endOfDay()
                           : due_date.endOfDay().isPast()
```

## 6. Migrasi frontend

- Ganti impor `Task` (dari [`resources/js/pages/project/index.d.ts`](../../../resources/js/pages/project/index.d.ts)) ke `App.Data.Task.ProjectTaskData` di ~29 file (daftar dirinci di plan).
- Pensiunkan interface `Task` hand-written; sesuaikan turunannya (`ProjectTaskTableProps`, `TaskFormatted.original`, `Sprint.tasks`, `ProjectDetailProps.tasks/backlog`, dll).
- Hapus penggunaan `users[].pivot` (tidak ada konsumen).
- Koreksi tipe `created_by`/`updated_by`/`deleted_by` → `number | null`.
- Verifikasi: `npx vue-tsc --noEmit` hijau.

## 7. Testing

Feature test untuk `project/Detail` (Pest):

- Fixture: project dengan pohon ≥3 level, ada epic + sub-epic, task dengan users(+avatar)/tags/media/creator, satu task soft-deleted, satu pivot user soft-deleted, satu task tanpa relasi.
- Assertions:
  - jumlah & **urutan** task sesuai (`sequence_number, id`);
  - struktur nesting `sub_task_recursive` benar di tiap level;
  - bentuk tiap field sesuai `ProjectTaskData` (status punya `score`, users punya `avatar_url` tanpa `pivot`, media punya `url`);
  - task & pivot soft-deleted **tidak** muncul;
  - **assert jumlah query** dengan `DB::enableQueryLog`: baris pohon = 1 query rekursif + jumlah query relasi **konstan** terlepas dari kedalaman (bandingkan pohon dangkal vs dalam → jumlah query sama);
  - parity: output baru setara output lama pada fixture yang sama.

Jalankan minimal: `php artisan test --filter=ProjectDetail`.

## 8. Risiko & mitigasi

1. **`Task::hydrate` melewati SoftDeletes global scope** → filter `deleted_at IS NULL` wajib ada di CTE (anchor & recursive). Diuji lewat fixture soft-deleted.
2. **`->load('media')` tanpa batas** memuat semua koleksi → dibatasi `collection_name = 'attachments'`; `users.media`/`creator.media` hanya koleksi `avatar` (User singleFile) sehingga aman.
3. **Cast tanggal pada hydrate**: pastikan kolom tanggal (`start_date`, `due_date`, `created_at`, `updated_at`) ikut di-SELECT agar cast & komputasi `is_overdue` benar.
4. **`vue-tsc` merah** pada field yang sebelumnya longgar (mis. `created_by`) — ditangani di tahap migrasi frontend.
5. **Konsistensi avatar di pohon**: perilaku lama pohon hanya `users:id,name` (tanpa avatar). Desain ini menambah `avatar_url` (penambahan, bukan regresi; `avatar_url` opsional).

## 9. Di luar cakupan

- Partial reload / lazy props Inertia untuk `show` (dibahas terpisah).
- Perubahan skema database.
- Raw SQL untuk sprint/backlog (non-rekursif; tetap Eloquent kecuali diminta).
