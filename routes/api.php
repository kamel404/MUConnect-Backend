<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\MajorController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\StudyGroupController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseCategoryController;
use App\Http\Controllers\PrerequisiteController;

// Auth routes (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public routes for registration form
Route::get('/registration/faculties', [FacultyController::class, 'index']);
Route::get('/registration/faculties/{id}/majors', [FacultyController::class, 'getFacultyMajors']);


// Moderator Routes
Route::middleware(['auth:sanctum', 'role:moderator'])->group(function () {
    // Faculty routes (protected)
    Route::get('/faculties', [FacultyController::class, 'index']);
    Route::post('/faculties', [FacultyController::class, 'store']);
    Route::get('/faculties/{id}', [FacultyController::class, 'show']);
    Route::put('/faculties/{id}', [FacultyController::class, 'update']);
    Route::delete('/faculties/{id}', [FacultyController::class, 'destroy']);

    // Major routes (protected)
    Route::get('/majors', [MajorController::class, 'index']);
    Route::post('/majors', [MajorController::class, 'store']);
    Route::get('/majors/{id}', [MajorController::class, 'show']);
    Route::put('/majors/{id}', [MajorController::class, 'update']);
    Route::delete('/majors/{id}', [MajorController::class, 'destroy']);
    Route::get('/majors/{id}/students', [MajorController::class, 'getMajorStudents']);

    // User roles management (protected)
    Route::get('/users/{id}/roles', [UserController::class, 'getUserRole']);
    Route::put('/users/{id}/roles', [UserController::class, 'updateUserRole']);
});


// Protected Routes for all authenticated users
Route::middleware('auth:sanctum')->group(function () {
    // Faculty routes (protected)
    Route::get('faculties/search/{query}', [FacultyController::class, 'search']);

    // Major routes (protected)
    Route::get('majors/search/{query}', [MajorController::class, 'search']);

    // User routes (protected)
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/me', [UserController::class, 'me']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Post routes (protected)

    // Study Groups routes
    Route::get('/study-groups', [StudyGroupController::class, 'index']);
    Route::post('/study-groups', [StudyGroupController::class, 'store']);
    Route::get('/study-groups/my-groups', [StudyGroupController::class, 'myGroups']);
    Route::get('/study-groups/{id}', [StudyGroupController::class, 'show']);
    Route::put('/study-groups/{id}', [StudyGroupController::class, 'update']);
    Route::delete('/study-groups/{id}', [StudyGroupController::class, 'destroy']);
    Route::get('/study-groups/search', [StudyGroupController::class, 'search']);
    Route::post('/study-groups/{group}/join', [StudyGroupController::class, 'joinGroup']);
    Route::post('/study-groups/{group}/leave', [StudyGroupController::class, 'leaveGroup']);
    Route::post('/study-groups/{group}/make-admin', [StudyGroupController::class, 'makeAdmin']);
    Route::get('/study-groups/filters', [StudyGroupController::class, 'applyFilters']);


    // Events routes
    Route::apiResource('/events', EventController::class)->except('create', 'edit');

    // Course routes
    Route::get('/courses', [CourseController::class, 'index']);
    Route::post('/courses', [CourseController::class, 'store']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);
    Route::put('/courses/{course}', [CourseController::class, 'update']);
    Route::delete('/courses/{course}', [CourseController::class, 'destroy']);

    // Course prerequisites routes
    Route::get('/courses/{course}/prerequisites', [PrerequisiteController::class, 'index']);
    Route::post('/courses/{course}/prerequisites', [PrerequisiteController::class, 'addPrerequisite']);
    Route::delete('/courses/{course}/prerequisites/{prerequisite}', [PrerequisiteController::class, 'removePrerequisite']);

    // Course Category routes
    Route::get('/course-categories', [CourseCategoryController::class, 'index']);
    Route::post('/course-categories', [CourseCategoryController::class, 'store']);
    Route::get('/course-categories/{category}', [CourseCategoryController::class, 'show']);
    Route::put('/course-categories/{category}', [CourseCategoryController::class, 'update']);
    Route::delete('/course-categories/{category}', [CourseCategoryController::class, 'destroy']);
});
