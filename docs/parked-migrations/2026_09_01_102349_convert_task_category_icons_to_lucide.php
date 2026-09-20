<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `task_categories.icon` menyimpan nama ikon PrimeVue (`pi pi-bolt`) dari stack lama.
 * PrimeVue dan PrimeIcons sudah tidak terpasang, sehingga nilai itu tidak bisa dirender
 * komponen ikon mana pun dan kolom Icon selalu tampil kosong. Konversi ke nama
 * lucide-vue-next yang dipakai komponen `Icon`.
 */
return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $map = [
        'pi pi-bolt' => 'Zap',
        'pi pi-bookmark' => 'Bookmark',
        'pi pi-info-circle' => 'CircleAlert',
        'pi pi-check-square' => 'SquareCheck',
        'pi pi-exclamation-triangle' => 'TriangleAlert',
    ];

    public function up(): void
    {
        foreach ($this->map as $prime => $lucide) {
            DB::table('task_categories')->where('icon', $prime)->update(['icon' => $lucide]);
        }
    }

    public function down(): void
    {
        foreach ($this->map as $prime => $lucide) {
            DB::table('task_categories')->where('icon', $lucide)->update(['icon' => $prime]);
        }
    }
};
