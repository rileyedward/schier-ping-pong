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
            $table->foreignId('team_one_id')->nullable()->after('player_two_id')->constrained('teams')->nullOnDelete();
            $table->foreignId('team_two_id')->nullable()->after('team_one_id')->constrained('teams')->nullOnDelete();
            $table->foreignId('winner_team_id')->nullable()->after('winner_id')->constrained('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropForeign(['team_one_id']);
            $table->dropForeign(['team_two_id']);
            $table->dropForeign(['winner_team_id']);
            $table->dropColumn(['team_one_id', 'team_two_id', 'winner_team_id']);
        });
    }
};
