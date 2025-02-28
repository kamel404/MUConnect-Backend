<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;

// User routes (public)
Route::get('/users/{id}', [UserController::class, 'show']);

// Auth routes (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Auth routes (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});

