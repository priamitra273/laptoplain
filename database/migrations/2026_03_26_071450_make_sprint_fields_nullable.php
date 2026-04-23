<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop CHECK constraint dulu
        DB::statement("
            ALTER TABLE project_sprints 
            DROP CONSTRAINT IF EXISTS project_sprints_duration_check
        ");

        Schema::table('project_sprints', function (Blueprint $table) {
            $table->string('duration')->nullable()->change();
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('project_sprints', function (Blueprint $table) {
            $table->string('duration')->nullable(false)->change();
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
        });

        // Optional: balikin constraint
        DB::statement("
            ALTER TABLE project_sprints 
            ADD CONSTRAINT project_sprints_duration_check 
            CHECK (duration IN ('1 week', '2 weeks', '3 weeks', '4 weeks'))
        ");
    }
};
