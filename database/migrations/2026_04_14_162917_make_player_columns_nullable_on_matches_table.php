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
        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('player_one_id')->nullable()->change();
            $table->foreignId('player_two_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('player_one_id')->nullable(false)->change();
            $table->foreignId('player_two_id')->nullable(false)->change();
        });
    }
};
