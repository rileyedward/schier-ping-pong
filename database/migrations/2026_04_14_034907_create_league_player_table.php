<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('league_player', function (Blueprint $table) {
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->primary(['league_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_player');
    }
};
