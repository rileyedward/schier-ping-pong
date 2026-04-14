<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('season_player', function (Blueprint $table) {
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->primary(['season_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_player');
    }
};
