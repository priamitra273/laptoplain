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
        Schema::create('project_sprints', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('sprint_status_id')->nullable();
            $table->foreign('sprint_status_id')
                ->references('id')
                ->on('ms_sprint_statuses')
                ->nullOnDelete();

            $table->string('name');
            $table->text('goal')->nullable();

            $table->enum('duration', ['1 week', '2 weeks', '3 weeks', '4 weeks']);

            $table->date('start_date');
            $table->date('end_date');

            $table->unsignedInteger('order')->default(0);
            $table->text('retrospective')->nullable();

            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_sprints');
    }
};
