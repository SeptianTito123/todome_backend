<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SubtaskController;


/* ... */

// == RUTE PUBLIK ==
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'googleLogin']);
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');


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

    // Rute untuk meng-update subtask (mencentang)
    Route::put('/subtasks/{subtask}', [SubtaskController::class, 'update']);
    
    // Rute untuk MENAMBAH subtask ke task yang ADA
    Route::post('/tasks/{task}/subtasks', [SubtaskController::class, 'store']);

    // Rute untuk menghapus subtask
    Route::delete('/subtasks/{subtask}', [SubtaskController::class, 'destroy']);
});