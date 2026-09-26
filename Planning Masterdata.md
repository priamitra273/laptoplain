# Planning Masterdata

Tanggal: 25 September 2026
Status: Perencanaan berdasarkan audit kode; implementasi belum dilakukan.

## Tujuan

Menyeragamkan halaman master data dengan memanfaatkan komponen yang sudah tersedia, mengurangi pengulangan kode, dan mempertahankan fungsi khusus setiap modul.

Audit awal dilakukan terhadap kode aktif, belum melalui pemeriksaan visual di browser. Dokumen ini merupakan rencana kerja, bukan persetujuan untuk menjalankan seluruh perubahan.

## Cakupan

Audit mencakup delapan modul dengan total 24 file Vue: halaman daftar, tabel, serta form tambah/edit.

| Modul            | Lokasi di `resources/js/pages/`   | Kebutuhan khusus                                                            |
| ---------------- | --------------------------------- | --------------------------------------------------------------------------- |
| Project Role     | `masterdata/ms_project_role/`     | Form halaman penuh, permission, pilihan banyak status, dan pembatasan field |
| Project Status   | `masterdata/ms_project_status/`   | Name dan Severity                                                           |
| Project Priority | `masterdata/ms_project_priority/` | Name dan Severity                                                           |
| Task Status      | `masterdata/ms_task_status/`      | Name, Severity, dan Score                                                   |
| Task Priority    | `masterdata/ms_task_priority/`    | Name dan Severity                                                           |
| Task Type        | `masterdata/ms_task_type/`        | Name dan Severity                                                           |
| Tag              | `tag/`                            | Name dan Severity                                                           |
| Task Category    | `task_category/`                  | Name, Severity, dan pemilih ikon                                            |

Tag dan Task Category disertakan karena memiliki route aktif dan halaman bertajuk master data, meskipun belum tercantum dalam kelompok Master Data di `MenuSeeder` yang diaudit. Menu aktual dapat berbeda dari seeder.

Folder `pages_v1` dan `components_v1` tidak menjadi target implementasi. Perubahan menu, dependency, database, permission, dan aturan bisnis tidak termasuk cakupan penyeragaman ini.

## Ringkasan audit

Halaman master data sudah cukup seragam pada komponen dasar. Masalah utama adalah susunan dan logika yang sama masih ditulis berulang, sehingga perubahan berikutnya berpotensi menghasilkan perbedaan antarhalaman.

Pola yang sudah baik dan perlu dipertahankan:

- Semua halaman daftar menggunakan `AppLayout` dan `Heading`.
- Konfirmasi hapus menggunakan `useConfirmDialog`.
- Tujuh form sederhana menggunakan `USlideover`.
- Tombol, input, dropdown, dan tabel menggunakan keluarga komponen UI yang sama.
- Visibilitas aksi mengikuti permission masing-masing modul.

## Daftar kandidat penyeragaman

| Prioritas | Bagian                                 | Target                                                      | Komponen tersedia                                                                  | Tindakan yang direncanakan                                                                                               |
| --------- | -------------------------------------- | ----------------------------------------------------------- | ---------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| Tinggi    | Tabel, sorting, pagination, dan footer | Seluruh delapan modul                                       | `components/ui/ServerDataTable.vue`                                                | Gunakan sebagai acuan tampilan; tentukan cara berbagi presentasi tanpa menghilangkan pagination dan pencarian di browser |
| Tinggi    | Label, tanda wajib, input, dan error   | Seluruh form                                                | `UFormField`, sudah digunakan pada `pages/favorites/project/ProjectFormDrawer.vue` | Ganti susunan manual dengan pola field yang konsisten, tetap memakai error dari form yang ada                            |
| Sedang    | Data kosong dan hasil pencarian kosong | Seluruh tabel                                               | `components/EmptyState.vue`                                                        | Gunakan tampilan kosong bersama dengan pesan sesuai keadaan                                                              |
| Sedang    | Pilihan severity                       | Tujuh form selain Project Role                              | `components/SeverityBadgeSelect.vue`                                               | Petakan opsi `label/value` menjadi `id/name/severity` tanpa mengubah nilai yang dikirim ke backend                       |
| Sedang    | Badge severity/status                  | Tujuh tabel dengan severity dan pilihan status Project Role | `components/StatusBadge.vue`                                                       | Tentukan standar visual sebelum mengganti badge langsung                                                                 |
| Sedang    | Kartu bagian form                      | Project Role                                                | `components/ui/PanelCard.vue`                                                      | Seragamkan judul dan spacing; tempatkan deskripsi pada isi atau slot yang sesuai                                         |
| Rendah    | Tampilan ikon kategori                 | Task Category                                               | `components/task/TaskCategoryBadge.vue`                                            | Evaluasi penggunaan `showLabel=false`, ukuran, tooltip, dan fallback ikon lama                                           |

### Batas kecocokan komponen

1. **ServerDataTable bukan pengganti langsung.** Master data saat ini mengelola pencarian, sorting, dan pagination di browser melalui TanStack. ServerDataTable menerima data dan kendali pagination/sorting dari parent. Mengganti komponen saja dapat mengubah perilaku atau menghasilkan pagination yang keliru.
2. **SeverityBadgeSelect membutuhkan pemetaan opsi.** Nilai severity harus tetap sama dengan nilai yang digunakan backend. Komponen ini belum mendukung pilihan banyak untuk Allowed Task Statuses pada Project Role.
3. **StatusBadge mengubah tampilan.** Komponen memakai badge netral dengan titik atau ikon berwarna; badge master data saat ini memakai latar berwarna. Ini keputusan visual, bukan sekadar pengurangan kode.
4. **PanelCard belum memiliki prop description.** Deskripsi form perlu ditempatkan secara eksplisit agar tetap tampil.
5. **TaskCategoryBadge memiliki fallback berbeda.** Penanganan ikon lama berformat `pi ...`, ikon kosong, dan ikon valid harus dipastikan sebelum digunakan.

### Bagian yang belum mempunyai pengganti langsung

- Komponen tabel bersama khusus pagination di browser.
- Pembungkus form master data untuk pola Name + Severity.
- Komponen pemilih ikon Lucide bersama. `EmojiPicker` memiliki fungsi berbeda.

Pembuatan komponen baru untuk bagian tersebut merupakan opsi lanjutan setelah menilai komponen yang tersedia, bukan hasil implementasi yang sudah ada.

## Tahapan pengerjaan

### Tahap 1 — Tetapkan acuan tampilan dan perilaku

- [ ] Periksa halaman aktif di browser, termasuk layar kecil dan mode gelap.
- [ ] Jadikan Project Status sebagai modul awal karena form dan tabelnya sederhana.
- [ ] Tetapkan pola footer, ukuran kontrol, spacing, badge, dan pesan data kosong.
- [ ] Tentukan pilihan jumlah baris: master data saat ini memakai 10/20/50, sedangkan default ServerDataTable 10/25/50.
- [ ] Pilih pendekatan berbagi komponen tabel yang mempertahankan pemrosesan data di browser. Migrasi ke pagination server merupakan pekerjaan terpisah bila diperlukan.
- [ ] Catat perilaku pencarian, sorting, perpindahan halaman, dan permission sebagai acuan verifikasi.

### Tahap 2 — Seragamkan tabel pada modul awal

- [ ] Kurangi duplikasi pembungkus tabel, header sorting, dan footer berdasarkan pendekatan tahap 1.
- [ ] Pertahankan konfigurasi kolom dan aksi setiap modul.
- [ ] Tampilkan ringkasan rentang hasil dan jumlah data yang sesuai dengan hasil pencarian.
- [ ] Gunakan `EmptyState` untuk data kosong dan pencarian tanpa hasil.
- [ ] Pastikan perubahan filter atau jumlah baris tidak menyisakan halaman di luar jangkauan.
- [ ] Verifikasi Project Status sebelum menerapkan pola ke modul lain.

### Tahap 3 — Seragamkan form dan severity

- [ ] Terapkan pola `UFormField` untuk label, tanda wajib, dan pesan error.
- [ ] Gunakan kembali `SeverityBadgeSelect` setelah pemetaan opsi dan perilaku nilai kosong tervalidasi.
- [ ] Terapkan standar badge yang telah dipilih.
- [ ] Samakan spacing field, lebar input, dan tombol submit pada form slideover.
- [ ] Pastikan nilai dan error form sesuai ketika berpindah antara tambah dan edit.
- [ ] Pertahankan loading dan pencegahan submit berulang saat proses berjalan.

### Tahap 4 — Terapkan ke seluruh modul

- [ ] Project Priority.
- [ ] Task Priority.
- [ ] Task Type.
- [ ] Tag.
- [ ] Task Status, termasuk field dan kolom Score.
- [ ] Task Category, termasuk pemilihan ikon dan data ikon lama.
- [ ] Project Role, termasuk tabel, kartu form, dan badge pilihan status.

Project Role tetap menggunakan halaman form penuh karena konfigurasi permission lebih kompleks. Pilihan banyak status tidak boleh berubah menjadi pilihan tunggal demi memakai komponen yang sama.

### Tahap 5 — Verifikasi akhir

- [ ] Jalankan pemeriksaan frontend yang tersedia dan relevan pada repository.
- [ ] Tambah atau sesuaikan pengujian perilaku untuk komponen/logika bersama yang berubah.
- [ ] Jalankan pengujian terdampak sebelum memperluas pengujian.
- [ ] Periksa seluruh delapan modul di browser setelah perubahan.
- [ ] Pastikan diff hanya mencakup penyeragaman yang disepakati.

## Kriteria penerimaan

| Area           | Hasil yang diharapkan                                                                                                 |
| -------------- | --------------------------------------------------------------------------------------------------------------------- |
| Tampilan tabel | Pembungkus, header, footer, ukuran kontrol, dan spacing mengikuti acuan yang sama                                     |
| Pencarian      | Hasil benar; jumlah hasil dan pagination mengikuti data yang telah difilter                                           |
| Sorting        | Arah pengurutan dan indikator kolom aktif tetap berfungsi                                                             |
| Pagination     | Perpindahan halaman, perubahan jumlah baris, dan kondisi hasil menyusut tidak menghasilkan halaman kosong yang keliru |
| Form           | Tambah/edit mengisi nilai yang benar; validasi muncul pada field terkait; submit berhasil dan gagal ditangani         |
| Severity       | Nilai tersimpan tetap sama; label dan warna konsisten pada pilihan dan tampilan data                                  |
| Permission     | Aksi tambah, edit, dan hapus tetap mengikuti izin pengguna                                                            |
| Hapus          | Pembatalan tidak menghapus data; konfirmasi memicu aksi yang benar                                                    |
| Project Role   | Permission, pilihan banyak status, dan batasan field tetap tersimpan sesuai perilaku sebelumnya                       |
| Task Status    | Nilai Score dan batas input tetap berfungsi                                                                           |
| Task Category  | Ikon valid tampil; ikon kosong dan format lama mendapat fallback yang sesuai                                          |
| Responsif      | Tabel, form, dan kontrol tetap dapat digunakan pada layar kecil dan mode gelap                                        |

## Status pekerjaan

- [x] Audit struktur halaman dan komponen yang tersedia.
- [x] Penyusunan dokumen perencanaan.
- [ ] Validasi visual melalui browser.
- [ ] Penetapan pendekatan implementasi.
- [ ] Implementasi dan pengujian.

Pada saat dokumen ini dibuat, belum ada perubahan kode aplikasi untuk menjalankan rencana di atas.
