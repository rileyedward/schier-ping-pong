<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('league'); // league | friendly
            $table->foreignId('season_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('player_one_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('player_two_id')->constrained('players')->cascadeOnDelete();
            $table->date('scheduled_for')->nullable();
            $table->timestamp('played_at')->nullable();
            $table->unsignedTinyInteger('best_of')->default(3);
            $table->unsignedTinyInteger('games_won_by_one')->nullable();
            $table->unsignedTinyInteger('games_won_by_two')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('players')->nullOnDelete();
            $table->timestamps();

            $table->index(['season_id', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
