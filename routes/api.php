<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FacultyController;

// User routes (public)
Route::get('/users/{id}', [UserController::class, 'show']);

// Auth routes (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Faculty routes (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/faculties', [FacultyController::class, 'index'])->middleware('role:super-admin|moderator');
    Route::post('/faculties', [FacultyController::class, 'store']);
    Route::get('/faculties/{id}', [FacultyController::class, 'show']);
    Route::put('/faculties/{id}', [FacultyController::class, 'update']);
    Route::delete('/faculties/{id}', [FacultyController::class, 'destroy']);

    Route::get('faculties/search/{name}', [FacultyController::class, 'search']);
    Route::get('faculties/search/abbreviation/{abbreviation}', [FacultyController::class, 'searchAbbreviation']);
});


// Auth routes (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::get('/users/{id}/roles', [UserController::class, 'getUserRole']);
});

