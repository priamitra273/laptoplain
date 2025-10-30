<?php

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
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('finish_date');
            $table->dropColumn('plan_site');
            $table->dropColumn('plan_cctv');
            
            $table->dropColumn('created_by');
            $table->dropColumn('updated_by'); 
            $table->dropColumn('deleted_by');

            $table->foreignId('status_id')->nullable()->constrained('ms_project_statuses')->nullOnDelete();
            $table->foreignId('priority_id')->nullable()->constrained('ms_project_priority')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owned_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('emoji')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->double('progress')->default(0);
            $table->integer('sequence_number')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('finish_date')->nullable();
            $table->integer('plan_site');
            $table->integer('plan_cctv');

            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();

            $table->dropColumn('emoji');
            $table->dropColumn('title');
            $table->dropColumn('description');
            $table->dropColumn('due_date');
            $table->dropColumn('progress');
            $table->dropColumn('sequence_number');

            $table->dropForeign(['status_id']);
            $table->dropForeign(['priority_id']);
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['owned_id']);
            
            $table->dropColumn('status_id');
            $table->dropColumn('priority_id');
            $table->dropColumn('owner_id');
            $table->dropColumn('owned_id');
        });
    }
};
