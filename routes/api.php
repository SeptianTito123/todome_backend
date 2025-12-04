<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SubtaskController;


// =======================
// RUTE PUBLIK
// =======================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'googleLogin']);

Route::get('/email/verify/{id}/{hash}', 
    [AuthController::class, 'verifyEmail']
)->name('verification.verify');


// =======================
// RUTE DILINDUNGI SANCTUM
// =======================
Route::middleware('auth:sanctum')->group(function () {

    // AUTH
    Route::post('/logout', [AuthController::class, 'logout']);

    // TASK (CRUD penuh)
    Route::apiResource('tasks', TaskController::class);

    // CATEGORY (CRUD penuh)
    Route::apiResource('categories', CategoryController::class);

    // DASHBOARD SUMMARY
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    // CALENDAR (task by date)
    Route::get('/calendar/tasks', [DashboardController::class, 'calendarTasks']);

    // =======================
    // SUBTASK ROUTE
    // =======================

    // Tambah subtask ke task tertentu
    Route::post('/tasks/{task}/subtasks', [SubtaskController::class, 'store']);

    // Update subtask (centang/rename)
    Route::put('/subtasks/{subtask}', [SubtaskController::class, 'update']);

    // Hapus subtask
    Route::delete('/subtasks/{subtask}', [SubtaskController::class, 'destroy']);


    // =======================
    // PROFILE
    // =======================
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::match(['put', 'post'], '/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::post('/save-fcm-token', [AuthController::class, 'saveFcmToken']);

});
    