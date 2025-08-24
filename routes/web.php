<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleSheetsController;

Route::get('/', function () {
    return view('welcome');
});

// Google OAuth + Sheets routes
Route::get('/google/auth', [GoogleSheetsController::class, 'auth'])->name('google.auth');
Route::get('/google/callback', [GoogleSheetsController::class, 'callback'])->name('google.callback');
Route::post('/google/sheets/rumble/create', [GoogleSheetsController::class, 'createRumbleSheet'])->middleware('auth')->name('google.sheets.rumble.create');
Route::post('/google/sheets/google-binom/create', [GoogleSheetsController::class, 'createGoogleBinomSheet'])->middleware('auth')->name('google.sheets.google_binom.create');
