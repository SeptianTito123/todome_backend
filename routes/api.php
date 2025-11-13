<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;


/* ... */

// == RUTE PUBLIK ==
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


// == RUTE YANG DIAMANKAN (WAJIB LOGIN/TOKEN) ==
Route::middleware('auth:sanctum')->group(function () {
    
    // Rute Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // Rute Task
    Route::apiResource('tasks', TaskController::class);

    // Rute Category
    // Catatan: Menambahkan CRUD API untuk Kategori (milik user)
    Route::apiResource('categories', CategoryController::class);

    // Catatan: Rute untuk Halaman Profile (Ringkasan)
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    // Catatan: Rute untuk Halaman Kalender (Tugas per tanggal)
    Route::get('/calendar/tasks', [DashboardController::class, 'calendarTasks']);
});