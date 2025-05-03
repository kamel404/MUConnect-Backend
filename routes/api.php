<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\MajorController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\TestController;
// User routes (public)

// Auth routes (public)
Route::get('/me', function () {
    return auth()->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/test', [TestController::class, 'testPost']);


//todo if we want we can move this to the protected routes

Route::get('/documents', [DocumentController::class, 'list']); // List all documents
Route::post('/documents/upload', [DocumentController::class, 'upload']); // Upload a document
Route::get('/download/{id}', [DocumentController::class, 'download']); // Download a document by ID





// Protected Routes
Route::middleware('auth:sanctum')->group(function () {

    // Faculty routes (protected)
    Route::get('faculties/search/{query}', [FacultyController::class, 'search']);

    // Major routes (protected)
    Route::get('majors/search/{query}', [MajorController::class, 'search']);

    // User routes (protected)
    Route::get('/users', [UserController::class, 'index']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    //logout 
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    }); // this function is for getting auth users 


});



// Super Admin and Moderator Routes
Route::middleware(['auth:sanctum', 'role:super-admin|moderator'])->group(function () {

    // Faculty routes (protected)
    Route::get('/faculties', [FacultyController::class, 'index']);
    Route::post('/faculties', [FacultyController::class, 'store']);
    Route::get('/faculties/{id}', [FacultyController::class, 'show']);
    Route::put('/faculties/{id}', [FacultyController::class, 'update']);
    Route::delete('/faculties/{id}', [FacultyController::class, 'destroy']);
    Route::get('/faculties/{id}/majors', [FacultyController::class, 'getFacultyMajors']);

    // Major routes (protected)
    Route::get('/majors', [MajorController::class, 'index']);
    Route::post('/majors', [MajorController::class, 'store']);
    Route::get('/majors/{id}', [MajorController::class, 'show']);
    Route::put('/majors/{id}', [MajorController::class, 'update']);
    Route::delete('/majors/{id}', [MajorController::class, 'destroy']);
    Route::get('/majors/{id}/students', [MajorController::class, 'getMajorStudents']);

    // User routes (protected)
    Route::get('/users/{id}/roles', [UserController::class, 'getUserRole']);
    Route::put('/users/{id}/roles', [UserController::class, 'updateUserRole']);


});

