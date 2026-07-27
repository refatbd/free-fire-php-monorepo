<?php

use App\Http\Controllers\PlayerPageController;
use Illuminate\Support\Facades\Route;
use Refatbd\FreeFire\Region\RegionRegistry;

Route::get('/', static fn () => view('welcome', [
    'regions' => RegionRegistry::SUPPORTED,
]))->name('home');
Route::view('/docs', 'docs')->name('docs');
Route::get('/player', [PlayerPageController::class, 'show'])->name('player.show');
