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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('owned_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('ms_task_statuses')->nullOnDelete();
            $table->foreignId('priority_id')->nullable()->constrained('ms_task_priorities')->nullOnDelete();
            $table->foreignId('type_id')->nullable()->constrained('ms_task_types')->nullOnDelete();
            
            
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('emoji', 100)->nullable();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->double('progress')->default(0);
            $table->integer('sequence_number')->nullable();
            $table->boolean('is_archived')->default(false);
            
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
