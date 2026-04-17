<?php

use App\Models\Comment;
use App\Models\User;
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
        Schema::create('comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Comment::class)->index();
            $table->foreignIdFor(User::class)->nullable()->index();
            $table->string('reaction');
            $table->timestamps();

            $table->index(['comment_id', 'user_id'], 'comment_reactions_unique');
            $table->index(['comment_id', 'user_id', 'reaction'], 'comment_reactions_unique_reaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_reactions');
    }
};
