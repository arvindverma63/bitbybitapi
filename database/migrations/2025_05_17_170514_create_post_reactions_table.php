<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_reactions', function (Blueprint $table) {
            $table->bigIncrements('reaction_id');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('user_id'); // Changed to unsignedBigInteger to match likely Users.user_id
            $table->enum('reaction_type', ['LIKE', 'DISLIKE']);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['post_id', 'user_id'], 'unique_reaction');

            $table->foreign('post_id')->references('post_id')->on('posts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_reactions');
    }
};
