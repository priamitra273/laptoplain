# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Register

product

<!-- deprecated: v4 replaced this axis with per-surface visitor modes. Pending deletion. -->

## Users

Tim internal Balitower — project manager, engineer, dan anggota tim yang mengelola proyek infrastruktur tower telekomunikasi. Pengguna bekerja dalam konteks operasional yang terstruktur: memantau progres, mendelegasikan task, dan berkolaborasi lintas divisi. Mereka menavigasi banyak proyek sekaligus dan membutuhkan informasi yang padat namun mudah dipahami dengan cepat.

## Product Purpose

Aplikasi project management internal untuk Balitower, menggantikan alur kerja manual (Excel, chat, email) dengan sistem terpusat. Sukses berarti tim bisa melihat status proyek, task, dan progres individu dalam satu tempat tanpa overhead administratif.

## Positioning

Dua hal yang tidak bisa ditiru PM tool generik tanpa kehilangan maknanya di sini:

1. **Permission granular per menu.** Role menentukan hak CRUD per menu item, sehingga setiap divisi hanya melihat dan mengubah apa yang relevan bagi mereka. Visibilitas adalah bagian dari model produk, bukan setting tambahan.
2. **Satu proyek, banyak sudut pandang.** Halaman proyek menyatukan detail, backlog, kanban, list, timeline, report, dan team dalam satu tempat — tim tidak berpindah tool atau kehilangan konteks saat berganti cara melihat pekerjaan yang sama.

## Operating Context

- **Desktop kantor, sesi panjang.** Layar lebar, banyak tab terbuka, dipakai berjam-jam. Density informasi dan alur keyboard lebih penting daripada ekspresi visual.
- **Dipresentasikan ke layar besar.** Report, timeline, dan kanban dibawa ke meeting tim atau manajemen, sehingga harus tetap terbaca pada jarak pandang jauh dan proyeksi.
- Menggantikan Excel, chat, dan email sebagai sumber kebenaran status proyek — struktur data dan istilah harus cukup jelas untuk dipercaya sebagai catatan resmi.

## Capabilities and Constraints

Fungsionalitas terkonfirmasi (Laravel + Inertia + Vue, 138 route):

- Proyek: CRUD, halaman detail dengan tab detail/backlog/kanban/list/timeline/report/team, summary, manajemen anggota beserta role proyek.
- Task: CRUD, update status/priority/parent, sub-task, bulk destroy, activity log, komentar dengan reaction dan mention, tag, kategori.
- Sprint: CRUD, start/complete, assign task, burndown dan status report.
- Master data dikelola dari UI: project status/priority/role, task status/priority/type/category, sprint status, menu, team, role, user.
- Dashboard dan workload user, laporan task dengan export, notifikasi (termasuk stream).

Kendala wajib:

- **Bahasa Indonesia untuk UI.** Label, pesan error, dan copy dalam Bahasa Indonesia; istilah teknis dan identifier kode tetap dalam bentuk aslinya.
- **Terminologi agile yang sudah dipakai** (sprint, backlog, story point, kanban) tidak diganti istilah lain — tim sudah terbiasa.
- **Struktur role & permission existing** tidak boleh disederhanakan atau di-bypass di lapisan UI; menu dan aksi mengikuti permission per role.
- Id entity di-encode (Sqids); id relasi datang sebagai string di runtime meski bertipe `number` di TypeScript.
- Frontend saat ini sedang dimigrasikan ke Nuxt UI v4 + Tailwind v4; komponen yang tersedia di library itu adalah batas praktis untuk pekerjaan UI baru.

## Brand Commitments

- Nama produk dan pemilik: Balitower (tool internal, bukan produk yang dijual).
- Kepribadian: **Profesional, Andal, Efisien** — tool yang "menghilang ke dalam pekerjaan." Pengguna tidak ingin terkesan dengan tampilannya; mereka ingin selesai lebih cepat.
- Bahasa antarmuka: Bahasa Indonesia.

## Anti-references

- **Jira**: UI padat, aging enterprise look, terlalu banyak layer navigasi.
- **Trello**: Terlalu kasual dan toy-like, tidak cukup serius untuk konteks operasional infrastruktur.
- **Generic SaaS**: Gradien biru-ungu, hero metric template, widget chart di mana-mana.
- **Dashboard overloaded**: Terlalu banyak visual sekaligus — semua terlihat penting sehingga tidak ada yang penting.

## Evidence on Hand

- Dokumentasi alur per domain di `docs/` (auth, project, task, sprint, role, team, user, menu, comment, notification, dashboard, master-data) dengan sequence diagram backend.
- Data nyata tersedia lewat seeder dan database dev bersama; pengujian manual lewat `localhost:8000` dengan akun seeder.
- Belum ada: testimonial, benchmark performa, angka adopsi, atau case study. Jangan mengarang salah satu pun.

## Product Principles

1. **Density serves clarity.** Informasi padat bukan berarti berantakan — setiap elemen harus punya alasan ada di layar. Jika bisa dihapus tanpa kehilangan fungsi, hapus.
2. **State is the signal.** Status pekerjaan (overdue, selesai, tertunda, blocked) adalah informasi paling berharga di setiap layar dan harus terbaca lebih dulu dari apa pun.
3. **Permission shapes the surface.** Apa yang dilihat pengguna mengikuti role-nya; layar tidak menawarkan aksi yang tidak boleh dilakukan.
4. **Earned familiarity.** Affordance mengikuti konvensi yang sudah dikenal tim sehingga onboarding nol. Jangan invent widget baru untuk hal standar.
5. **The tool should disappear.** Tidak ada langkah, hiasan, atau ceremony yang tidak membantu menyelesaikan pekerjaan lebih cepat.

## Design Principles

<!-- legacy: panduan visual, pindah ke DESIGN.md saat new-work berjalan -->

1. Warna dan visual digunakan untuk menyampaikan status — bukan dekorasi.
2. Motion hanya pada state change, bukan choreography. Tidak ada intro sequence, tidak ada splash.
3. Dark mode bukan opsional. Tim operasional bekerja di berbagai kondisi cahaya; dark mode harus setara dengan light mode, bukan afterthought.
4. Vibes referensi: Linear, Notion.

## Accessibility & Inclusion

WCAG AA minimum. Keyboard navigation untuk semua aksi utama. Tidak ada informasi yang hanya disampaikan via warna saja — selalu ada ikon atau label pendamping. Report dan timeline harus tetap terbaca saat diproyeksikan ke layar besar.
