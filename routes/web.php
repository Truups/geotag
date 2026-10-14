<?php

use App\Http\Controllers\GeotagCsvController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/geotag', [GeotagCsvController::class, 'create'])->name('geotag.create');
Route::post('/geotag', [GeotagCsvController::class, 'process'])->name('geotag.process');
