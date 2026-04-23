<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTaskCategoryIdToTasksTable extends Migration
{
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {

            $table->foreignId('task_category_id')
                ->nullable()
                ->after('type_id')
                ->constrained('task_categories')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {

            $table->dropForeign(['task_category_id']);
            $table->dropColumn('task_category_id');
        });
    }
}
