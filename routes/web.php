<?php

use App\Http\Controllers\Admin\LeagueController;
use App\Http\Controllers\Admin\MatchupController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Admin\SeasonController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicMatchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('matches/friendly', [PublicMatchController::class, 'storeFriendly'])->name('matches.friendly');
Route::post('matches/{matchup}/result', [PublicMatchController::class, 'storeLeagueResult'])->name('matches.result');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('players', PlayerController::class)->except('show');
        Route::resource('leagues', LeagueController::class);
        Route::post('leagues/{league}/players', [LeagueController::class, 'attachPlayer'])->name('leagues.players.attach');
        Route::delete('leagues/{league}/players/{player}', [LeagueController::class, 'detachPlayer'])->name('leagues.players.detach');

        Route::resource('seasons', SeasonController::class);
        Route::post('seasons/{season}/players', [SeasonController::class, 'attachPlayer'])->name('seasons.players.attach');
        Route::delete('seasons/{season}/players/{player}', [SeasonController::class, 'detachPlayer'])->name('seasons.players.detach');

        Route::post('seasons/{season}/matches', [MatchupController::class, 'storeForSeason'])->name('seasons.matches.store');
        Route::post('seasons/{season}/matches/generate', [MatchupController::class, 'generateSchedule'])->name('seasons.matches.generate');
        Route::put('matches/{matchup}', [MatchupController::class, 'update'])->name('matches.update');
        Route::post('matches/{matchup}/score', [MatchupController::class, 'recordScore'])->name('matches.score');
        Route::delete('matches/{matchup}', [MatchupController::class, 'destroy'])->name('matches.destroy');
    });
});

require __DIR__.'/settings.php';
