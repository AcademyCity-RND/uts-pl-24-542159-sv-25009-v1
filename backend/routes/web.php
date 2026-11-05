<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Endpoint Pemeriksaan Kesehatan Sistem (P10)
Route::get('/health', [HealthController::class, 'check']);
