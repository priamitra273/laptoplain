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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('severities', 50)->nullable();

            // Created by Deleted by and Updated by
            $table->foreignId('owned_id')->nullable()->constrained('users')->nullOnDelete();
            // $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            // $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
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
        Schema::dropIfExists('tags');
    }
};
