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
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\SectionRequestController;
use App\Http\Controllers\ApplicationController;

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

    // Events routes (protected)
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'update']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);
    
});


// Protected Routes for all authenticated users
Route::middleware('auth:sanctum')->group(function () {

    // Saved Items routes 
    Route::get('/saved-items', [\App\Http\Controllers\SavedItemController::class, 'index']);

    // Resource routes (protected)
    Route::post('/resources', [\App\Http\Controllers\ResourceController::class, 'store']);
    Route::put('/resources/{id}', [\App\Http\Controllers\ResourceController::class, 'update']);
    Route::delete('/resources/{id}', [\App\Http\Controllers\ResourceController::class, 'destroy']);
    Route::post('/resources/{id}/save', [\App\Http\Controllers\ResourceController::class, 'save']);
    Route::delete('/resources/{id}/unsave', [\App\Http\Controllers\ResourceController::class, 'unsave']);

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
    Route::post('/study-groups/{group}/save', [StudyGroupController::class, 'save']);
    Route::post('/study-groups/{group}/unsave', [StudyGroupController::class, 'unsave']);


    // Events routes
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/my-events', [EventController::class, 'myRegisteredEvents']);
    Route::get('/events/{event}', [EventController::class, 'show']);
    Route::post('/events/{event}/register', [EventController::class, 'register']);
    Route::post('/events/{event}/unregister', [EventController::class, 'unregister']);
    Route::post('/events/{event}/save', [EventController::class, 'save']);
    Route::post('/events/{event}/unsave', [EventController::class, 'unsave']);

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

    // Requests routes
    Route::get('/requests', [SectionRequestController::class, 'index']);
    Route::get('/my-requests', [SectionRequestController::class, 'myRequests']);
    Route::post('/requests', [SectionRequestController::class, 'store']);
    Route::get('/requests/{request}', [SectionRequestController::class, 'show']);
    Route::put('/requests/{request}', [SectionRequestController::class, 'update']);
    Route::delete('/requests/{request}', [SectionRequestController::class, 'destroy']);

    // Applications routes
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::get('/applications/{application}', [ApplicationController::class, 'show']);
    Route::post('/requests/{request}/apply', [ApplicationController::class, 'store']);
    Route::put('/applications/{application}', [ApplicationController::class, 'update']);
    Route::get('/requests/{request}/applications', [ApplicationController::class, 'forRequest']);
});
