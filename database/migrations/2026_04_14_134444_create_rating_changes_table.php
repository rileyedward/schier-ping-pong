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
        Schema::create('rating_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matchup_id')->constrained('matches')->cascadeOnDelete();
            $table->string('type');
            $table->unsignedInteger('rating_before');
            $table->unsignedInteger('rating_after');
            $table->integer('delta');
            $table->timestamp('created_at')->nullable();

            $table->unique(['player_id', 'matchup_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rating_changes');
    }
};
