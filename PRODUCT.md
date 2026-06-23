# Product

## Register

product

## Users

Tim internal Balitower — project manager, engineer, dan anggota tim yang mengelola proyek infrastruktur tower telekomunikasi. Pengguna bekerja dalam konteks operasional yang terstruktur: memantau progres, mendelegasikan task, dan berkolaborasi lintas divisi. Mereka menavigasi banyak proyek sekaligus dan membutuhkan informasi yang padat namun mudah dipahami dengan cepat.

## Product Purpose

Aplikasi project management internal untuk Balitower, menggantikan alur kerja manual (Excel, chat, email) dengan sistem terpusat. Sukses berarti tim bisa melihat status proyek, task, dan progres individu dalam satu tempat tanpa overhead administratif.

## Brand Personality

Profesional, Andal, Efisien — tool yang "menghilang ke dalam pekerjaan." Pengguna tidak ingin terkesan dengan tampilannya; mereka ingin selesai lebih cepat.

## Anti-references

- **Jira**: UI padat, aging enterprise look, terlalu banyak layer navigasi.
- **Trello**: Terlalu kasual dan toy-like, tidak cukup serius untuk konteks operasional infrastruktur.
- **Generic SaaS**: Gradien biru-ungu, hero metric template, widget chart di mana-mana.
- **Dashboard overloaded**: Terlalu banyak visual sekaligus — semua terlihat penting sehingga tidak ada yang penting.

## Design Principles

1. **Density serves clarity.** Informasi padat bukan berarti berantakan — setiap elemen harus punya alasan ada di layar. Jika bisa dihapus tanpa kehilangan fungsi, hapus.
2. **State is the signal.** Warna dan visual digunakan untuk menyampaikan status (overdue, selesai, tertunda) — bukan dekorasi.
3. **Earned familiarity.** Affordance mengikuti konvensi yang sudah dikenal tim (Linear, Notion vibes) sehingga onboarding nol. Jangan invent widget baru untuk hal standar.
4. **The tool should disappear.** Motion hanya pada state change, bukan choreography. Tidak ada intro sequence, tidak ada splash.
5. **Dark mode bukan opsional.** Tim operasional bekerja di berbagai kondisi cahaya; dark mode harus setara dengan light mode, bukan afterthought.

## Accessibility & Inclusion

WCAG AA minimum. Keyboard navigation untuk semua aksi utama. Tidak ada informasi yang hanya disampaikan via warna saja — selalu ada ikon atau label pendamping.
