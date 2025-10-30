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
        Schema::dropIfExists('task_projects');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('task_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('status_id')->nullable()->constrained('ms_project_statuses')->nullOnDelete();
            $table->foreignId('priority_id')->nullable()->constrained('ms_project_priority')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owned_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('emoji')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->double('progress')->default(0);
            $table->integer('sequence_number')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
