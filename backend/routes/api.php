<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PublicItemController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Endpoint Publik Terbuka
Route::get('/public/items', [PublicItemController::class, 'index']);

// Endpoint Autentikasi Publik (Rate Limit: 5 req/menit per IP)
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');

// Endpoint Autentikasi Terproteksi (Bearer Token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return response()->json([
            'message' => 'Profil pengguna berhasil diambil.',
            'data' => $request->user(),
        ]);
    });
});
