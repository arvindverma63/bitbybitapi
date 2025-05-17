<?php

  use Illuminate\Database\Migrations\Migration;
  use Illuminate\Database\Schema\Blueprint;
  use Illuminate\Support\Facades\Schema;

  return new class extends Migration
  {
      public function up(): void
      {
          Schema::table('threads', function (Blueprint $table) {
              // Example: Change userId to BIGINT if needed
              $table->unsignedBigInteger('userId')->change();
              // Add any missing columns
              if (!Schema::hasColumn('threads', 'notification')) {
                  $table->integer('notification')->default(0);
              }
              // Adjust other columns as needed
          });
      }

      public function down(): void
      {
          Schema::table('threads', function (Blueprint $table) {
              $table->integer('userId')->change();
              $table->dropColumn('notification');
          });
      }
  };
