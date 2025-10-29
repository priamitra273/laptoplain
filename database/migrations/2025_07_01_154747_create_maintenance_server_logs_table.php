<?php

use App\Models\AnalyticServer;
use App\Models\Hardware;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_server_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(AnalyticServer::class)->constrained();
            $table->foreignIdFor(Hardware::class, 'cpu_id')->nullable()->constrained();
            $table->foreignIdFor(Hardware::class, 'gpu_id')->nullable()->constrained();
            $table->foreignIdFor(Hardware::class, 'ram_id')->nullable()->constrained();
            $table->foreignIdFor(Hardware::class, 'ssd_id')->nullable()->constrained();
            $table->foreignIdFor(Hardware::class, 'mobo_id')->nullable()->constrained();
            $table->foreignIdFor(Hardware::class, 'nic_id')->nullable()->constrained();
            $table->foreignIdFor(Hardware::class, 'psu_id')->nullable()->constrained();
            $table->foreignIdFor(Hardware::class, 'lc_id')->nullable()->constrained();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_server_logs');
    }
};
