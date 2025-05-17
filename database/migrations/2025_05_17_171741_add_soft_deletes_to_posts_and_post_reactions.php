<?php

  use Illuminate\Database\Migrations\Migration;
  use Illuminate\Database\Schema\Blueprint;
  use Illuminate\Support\Facades\Schema;

  return new class extends Migration
  {
      public function up(): void
      {
          Schema::table('posts', function (Blueprint $table) {
              if (!Schema::hasColumn('posts', 'deleted_at')) {
                  $table->softDeletes();
              }
          });

          Schema::table('post_reactions', function (Blueprint $table) {
              if (!Schema::hasColumn('post_reactions', 'deleted_at')) {
                  $table->softDeletes();
              }
          });
      }

      public function down(): void
      {
          Schema::table('posts', function (Blueprint $table) {
              $table->dropSoftDeletes();
          });

          Schema::table('post_reactions', function (Blueprint $table) {
              $table->dropSoftDeletes();
          });
      }
  };
