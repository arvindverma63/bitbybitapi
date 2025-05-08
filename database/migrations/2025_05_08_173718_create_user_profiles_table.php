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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId');
            $table->string('firstName');
            $table->string('lastName')->nullable();
            $table->text('avatar')->nullable();
            $table->text('banners')->nullable();
            $table->string('lastseen')->nullable();
            $table->text('about')->nullable();
            $table->timestamps();

            // Correct foreign key definition
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
