<?php

namespace Database\Seeders;

use App\Models\Hardware\HardwareStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HardwareStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $inputs = [
            ['name' => 'New', 'level' => 1, 'description' => 'Barang dalam kondisi sempurna, belum pernah digunakan, segel masih utuh, tanpa cacat.'],
            ['name' => 'Used', 'level' => 2, 'description' => 'Barang bekas, ada tanda penggunaan seperti goresan atau noda ringan, namun tetap berfungsi dengan baik.'],
            ['name' => 'Damaged', 'level' => 3, 'description' => 'Barang rusak, beberapa bagian tidak berfungsi atau ada kerusakan yang signifikan, perlu perbaikan.'],
            ['name' => 'Defective', 'level' => 3, 'description' => 'Barang memiliki cacat produksi atau kesalahan pada bagian tertentu, tetapi masih bisa digunakan meskipun tidak sempurna.'],
            ['name' => 'Good Condition', 'level' => 2, 'description' => 'Barang dalam kondisi sangat baik, tanpa kerusakan signifikan, hanya sedikit tanda penggunaan.'],
            ['name' => 'Sealed', 'level' => 1, 'description' => 'Barang baru, segel masih utuh, belum dibuka atau digunakan, siap pakai.'],
            ['name' => 'Functional Condition', 'level' => 2, 'description' => 'Barang berfungsi dengan baik meskipun ada tanda penggunaan, tetapi masih optimal dalam kinerjanya.'],
            ['name' => 'Limited Stock', 'level' => 1, 'description' => 'Barang baru dengan stok terbatas, kondisi masih sempurna dan siap digunakan.'],
            ['name' => 'Under Repair', 'level' => 4, 'description' => 'Barang sedang dalam perbaikan dan belum siap digunakan. Belum ada jaminan bahwa barang akan kembali berfungsi normal.'],
            ['name' => 'Repaired but Non-functional', 'level' => 5, 'description' => 'Barang telah diperbaiki, namun tidak dapat berfungsi sebagaimana mestinya. Tidak bisa digunakan untuk tujuan semula.'],
            ['name' => 'Beyond Repair, Unrepairable', 'level' => 5, 'description' => 'Barang mengalami kerusakan yang sangat parah dan tidak dapat diperbaiki.'],
        ];

        foreach ($inputs as $value) {
            HardwareStatus::create($value);
        }
    }
}
