---
target: resources/js/pages/project-lazy/List.vue
total_score: 27
p0_count: 0
p1_count: 2
timestamp: 2026-06-20T09-46-32Z
slug: resources-js-pages-project-lazy-list-vue
---
# Critique — project-lazy/List.vue (List tab proyek)

Target: `resources/js/pages/project-lazy/List.vue` → surface riil = `ProjectShellLayout` (header + stats + tabs) + `TaskTableToolbar` + `TaskTableFilters` + PrimeVue `TreeTable` + `TaskFormDrawer` + `ListTableSkeleton`. Register: product.

## Design Health Score

| # | Heuristic | Skor | Isu Kunci |
|---|-----------|------|-----------|
| 1 | Visibility of System Status | 3 | Skeleton Deferred + toast + feedback drag bagus; toast sukses bulk-delete muncul sebelum request resolve |
| 2 | Match System / Real World | 3 | Terminologi domain solid (Task/Status/Type/Subtask); empty state "No Data Available" robotik |
| 3 | User Control and Freedom | 3 | Drawer escape, Clear Filters, dialog konfirmasi delete; reparent via drag tanpa undo |
| 4 | Consistency and Standards | 3 | PrimeVue konsisten; warna state pakai palet Tailwind mentah (blue/emerald/red), bukan token surface/severity |
| 5 | Error Prevention | 3 | Konfirmasi delete + pola hold-to-arm drag bagus; loop bulk-delete tak menunggu resolusi |
| 6 | Recognition Rather Than Recall | 2 | 5 tombol ikon-only per baris, tab ikon-only di mobile, affordance drag tersembunyi |
| 7 | Flexibility and Efficiency | 3 | Filter persist (sessionStorage), sort, bulk-select, frozen columns; tak ada keyboard shortcut, bulk hanya delete |
| 8 | Aesthetic and Minimalist Design | 3 | Toolbar/filter bersih; cluster 5 tombol aksi ramai untuk register tabel |
| 9 | Error Recovery | 2 | Toast on-fail untuk single delete; bulk-delete tanpa penanganan error & klaim sukses tanpa syarat |
| 10 | Help and Documentation | 2 | Hanya tooltip; empty state tak mengajar; tak ada onboarding |
| **Total** | | **27/40** | **Acceptable (atas) — fondasi kompeten, beberapa perbaikan terarah mendorong ke Good** |

## Anti-Patterns Verdict

**LLM assessment:** TIDAK terlihat AI-generated. Ini produk PrimeVue (tema Avalon) yang dirakit dengan benar — nol gradient text, nol eyebrow tracked, nol side-stripe border, nol hero-metric. Earned familiarity sesuai register product. Kegagalannya bukan "slop", melainkan gap craft produk: empty state lemah, status overdue via warna saja, kepadatan aksi.

**Deterministic scan:** `detect.mjs` atas 7 file surface → `[]`, exit 0, **0 temuan**. Bersih. Catatan: detektor menilai markup tell, bukan drift token warna — temuan warna state di bawah berasal dari review manual, bukan scanner.

**Visual overlays:** Tidak tersedia. Tidak ada browser automation di environment ini, jadi tak ada overlay yang diinjeksi. Assessment B turun ke scan CLI saja (dilaporkan sebagai fallback, bukan diklaim).

## Overall Impression

Tabel produk yang matang dan dapat dipercaya pengguna fluent kategori (Linear/Notion vibe) — tool yang "menghilang ke dalam pekerjaan" sesuai PRODUCT.md. Yang menahannya bukan estetika tapi tiga retakan: (1) status overdue disampaikan **hanya** lewat warna merah, melanggar prinsip aksesibilitas proyek sendiri; (2) bulk-delete mengklaim sukses tanpa menunggu hasil; (3) empty state tak mengajar. Peluang terbesar: tutup gap "state is the signal" — pasang ikon/label pendamping pada warna, dan jujurkan feedback bulk action.

## What's Working

- **Deferred + skeleton yang match layout.** `ListTableSkeleton` meniru toolbar/filter/tabel persis, bukan spinner di tengah. Inertia v2 dipakai benar. Loading terasa tenang.
- **Error prevention pada delete tunggal.** Dialog konfirmasi dengan teks "This action cannot be undone", `acceptClass=p-button-danger`, label eksplisit "Yes, remove". Tepat untuk aksi destruktif.
- **Filter yang menghargai waktu.** Search + MultiSelect status/type dengan Tag berwarna di opsi, persist per-user via `useSessionStorage`, frozen columns kiri-kanan untuk scroll horizontal. Ini efisiensi nyata untuk PM yang scanning.

## Priority Issues

### [P1] Status overdue hanya disampaikan lewat warna
- **Why it matters:** `:class="{ 'text-red-500': node.data.is_overdue }"` pada Due Date adalah satu-satunya penanda overdue. PRODUCT.md menyatakan eksplisit: "Tidak ada informasi yang hanya disampaikan via warna saja — selalu ada ikon atau label pendamping." Pengguna buta warna / screen reader tidak tahu task telat. Melanggar prinsip aksesibilitas yang ditetapkan sendiri + WCAG (Sam).
- **Fix:** Tambah ikon (`pi pi-exclamation-circle`) atau pill "Overdue" di samping tanggal saat `is_overdue`. Pakai token severity, bukan `text-red-500` mentah.

### [P1] Bulk-delete mengklaim sukses tanpa menunggu hasil & tanpa penanganan error
- **Why it matters:** `removeSelected` me-loop `router.delete` untuk tiap id, lalu langsung `toast success "${ids.length} tasks deleted successfully"` — tanpa `onError`, tanpa menunggu resolusi. Jika sebagian/semua request gagal, pengguna tetap melihat "berhasil" sementara task masih ada (visibilitas status palsu). Stress-tester (Riley) langsung menemukan ini. Bandingkan dengan single-`remove` yang punya `onError`.
- **Fix:** Kirim satu request bulk (atau `Promise.all` lalu hitung sukses/gagal), tampilkan toast berdasarkan hasil nyata, dan tangani kegagalan parsial ("3 dari 5 terhapus, 2 gagal").

### [P2] Empty state tak mengajar
- **Why it matters:** `#empty` = `<p class="text-center">No Data Available</p>`. Untuk proyek baru (first-run) ini titik di mana pengguna paling butuh arahan. PRODUCT.md: "Empty states yang mengajar interface, bukan 'nothing here.'" Tombol "Add Task" ada di toolbar (sehingga bukan blocker), tapi empty state ini menyia-nyiakan momen aktivasi.
- **Fix:** Empty state dengan ikon, kalimat ("Belum ada task di proyek ini"), dan CTA "Add Task" yang memanggil `emit('add', null)` langsung dari tengah tabel.

### [P2] Lima tombol ikon-only per baris membebani recognition & sentuhan
- **Why it matters:** View / Add Subtask / Edit / Delete / History — semuanya ikon-only dengan `v-tooltip`. Tooltip tak muncul di touch maupun keyboard-focus; first-timer (Jordan) harus menebak, dan cluster 5 > batas working memory (≤4). Delete (danger) berdempetan dengan Edit & History → risiko misclick di aksi destruktif. Dikalikan N baris = bising visual.
- **Fix:** Pertahankan View/Edit inline; lipat Add Subtask + History (+ Delete) ke dalam satu overflow menu (`pi pi-ellipsis-v`) per baris. Beri `aria-label` pada setiap tombol ikon.

### [P2] Judul ter-truncate keras di 150px + warna state lepas dari sistem token
- **Why it matters:** Judul adalah identitas utama di List view, tapi `max-w-[150px] truncate` mengkliping-nya bahkan di layar lebar — justru ruang horizontal yang menjadi alasan memilih List. (Native `:title` memulihkan teks penuh saat hover, tapi tidak di touch.) Terpisah: state drag & overdue memakai `blue-100/emerald-400/red-500` mentah + varian `dark:` ad-hoc, sementara sisa app pakai token `surface-*` + severity PrimeVue → drift konsistensi & tema.
- **Fix:** Naikkan/lepas batas lebar judul agar elastis (mis. `max-w-[28ch]` atau lebar kolom yang tumbuh). Petakan warna state ke token/severity tema, bukan literal palet.

## Persona Red Flags

**Alex (Power User, data-heavy):** Tak ada keyboard shortcut untuk aksi umum (add/edit/delete). Bulk action hanya "Delete Selected" — tak ada bulk set-status / bulk assign yang natural di tabel task. Sorting & filter persist membantu, tapi alur tetap mouse-first.

**Sam (Accessibility):** Overdue via warna saja (gagal). Tombol aksi ikon-only tanpa `aria-label` terbaca samar oleh screen reader. Drag-reparent mouse/pointer-only — tak ada jalur keyboard (termitigasi: reparent juga bisa via field parent di Edit drawer, jadi bukan blocker total). `opacity-50` pada judul non-draggable bisa jatuh di bawah 4.5:1.

**Rina (PM Balitower, project-specific — menavigasi banyak proyek sekaligus):** Saat membuka proyek baru, disambut "No Data Available" tanpa arahan. Saat menghapus beberapa task sekaligus, mendapat konfirmasi "berhasil" yang mungkin tidak benar — lalu menavigasi pergi dengan asumsi keliru. Density tabel justru kekuatan untuknya, tapi judul ter-clip 150px memaksa hover untuk membaca task yang ia kenal.

## Minor Observations

- Copy kolom "Complete Date" lebih tepat "Completed Date"/"Completed".
- `moment` di-import untuk satu `formatDate` — pertimbangkan native `Intl.DateTimeFormat` / dayjs (perf bundle).
- `hasAccessToEditAndDelete()` dipanggil sebagai method di template (baris 168/242/250) — jadikan computed.
- Tab ikon-only di mobile (`hidden sm:inline`) bergantung tooltip yang lemah di touch; 7 tab scrollable.
- `scrollHeight="600px"` hardcoded — kaku lintas tinggi viewport.
- Status & Type sama-sama render sebagai `Tag` severity → secara visual nyaris identik, membaurkan dua dimensi berbeda.

## Questions to Consider

- Apa versi "percaya diri" dari kolom Actions — kalau hanya View + Edit yang inline dan sisanya di overflow, apakah tabel jadi lebih tenang tanpa kehilangan fungsi?
- Kalau "state is the signal", apakah setiap warna di layar ini sudah punya ikon/label pendamping — atau merah masih berbicara sendirian?
- Bulk action: haruskah feedback mencerminkan kebenaran server, bukan optimisme klien?
