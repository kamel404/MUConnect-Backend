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
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\VotingController;
use App\Http\Controllers\EventRegistrationController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\AIQuizController;


// Auth routes (public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Email verification routes
Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\VerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
Route::post('/email/resend', [\App\Http\Controllers\VerificationController::class, 'resend']);

// Storage proxy route to handle CORS for files
Route::get('/storage/{path}', [StorageController::class, 'proxyFile'])->where('path', '.*');

// Auth routes (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/google/logout', [GoogleAuthController::class, 'logout']);
});

// Public routes for registration form
Route::get('/registration/faculties', [FacultyController::class, 'index']);
Route::get('/registration/faculties/{id}/majors', [FacultyController::class, 'getFacultyMajors']);

// Google OAuth authentication completion route
Route::post('/auth/google/complete-registration', [GoogleAuthController::class, 'completeRegistration'])->name('auth.google.complete');


// Moderator Routes
Route::middleware(['auth:sanctum', 'role:moderator|admin'])->group(function () {
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
    Route::patch('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);
    Route::get('/users', [UserController::class, 'index']);

    // Events routes (protected)
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'update']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);

    // Clubs routes (protected)
    Route::get('/clubs/{club}/members', [ClubController::class, 'members']);
    Route::post('/clubs', [ClubController::class, 'store']);


    // Club Events routes (protected)
    Route::post('/clubs/{club}/events', [ClubController::class, 'createClubEvent']);

    // Votes routes (protected)
    Route::post('clubs/{club}/candidates', [App\Http\Controllers\VotingController::class, 'addCandidate']);
    Route::post('/voting-status', [App\Http\Controllers\VotingController::class, 'toggleSystemVoting']);
});


// Protected Routes for all authenticated users
Route::middleware('auth:sanctum')->group(function () {

    // Resource routes (protected)
    Route::get('/resources', [ResourceController::class, 'index']);
    Route::get('/top-contributors', [ResourceController::class, 'topContributors']);
    Route::get('/resources/{id}', [ResourceController::class, 'show']);
    Route::post('/resources', [ResourceController::class, 'storeTest']);
    Route::put('/resources/{id}', [ResourceController::class, 'updateTest']);
    Route::delete('/resources/{id}', [ResourceController::class, 'destroyTest']);
    Route::post('/resources/{id}/toggleSave', [ResourceController::class, 'toggleSave']);
    Route::post('/resources/{id}/toggle-upvote', [ResourceController::class, 'toggleUpvote']);
    
    // Poll routes (protected)
    Route::post('/poll-options/{id}/vote', [ResourceController::class, 'votePollOption']);
    
    // Resource comments routes
    Route::get('/resources/{id}/comments', [\App\Http\Controllers\CommentController::class, 'getResourceComments']);
    Route::post('/resources/{id}/comments', [\App\Http\Controllers\CommentController::class, 'addResourceComment']);
    Route::put('/comments/{id}', [\App\Http\Controllers\CommentController::class, 'updateComment']);
    Route::delete('/comments/{id}', [\App\Http\Controllers\CommentController::class, 'deleteComment']);
    Route::post('/comments/{id}/toggle-upvote', [\App\Http\Controllers\CommentController::class, 'toggleUpvote']);

    // View own profile
    Route::get('/my-profile', [UserController::class, 'profile']);
    // Dashboard overview
    Route::get('/overview', [\App\Http\Controllers\OverviewController::class, 'index']);
    // View someone else's profile
    Route::get('/profile/{id}', [UserController::class, 'profile']);
    // Get recent activity
    Route::get('/profile/activity', [UserController::class, 'recentActivity']);
    // update profile
    Route::put('/user/{id}', [UserController::class, 'update']);

    // Saved Items routes 
    Route::get('/saved-items', [\App\Http\Controllers\SavedItemController::class, 'index']);

    // Resource routes (protected)
    Route::apiResource('events.registrations', App\Http\Controllers\EventRegistrationController::class)->shallow();

    // Voting routes
    Route::get('clubs/{club}/candidates', [App\Http\Controllers\VotingController::class, 'getCandidates']);
    Route::post('clubs/{club}/vote', [App\Http\Controllers\VotingController::class, 'vote']);
    Route::get('clubs/{club}/results', [App\Http\Controllers\VotingController::class, 'results']);
    Route::get('clubs/{club}/vote-status', [App\Http\Controllers\VotingController::class, 'getVoteStatus']);
    Route::get('/voting-status', [App\Http\Controllers\VotingController::class, 'getVotingStatus']);

    // Faculty routes (protected)
    Route::get('faculties/search/{query}', [FacultyController::class, 'search']);

    // Major routes (protected)
    Route::get('majors/search/{query}', [MajorController::class, 'search']);

    // User routes (protected)
    Route::get('/users/me', [UserController::class, 'me']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
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
    Route::post('/events/{event}/toggleSave', [EventController::class, 'toggleSave']);
    // Club Events routes
    Route::get('/clubs/{club}/events', [ClubController::class, 'clubEvents']);

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
    Route::get('/my-applications', [ApplicationController::class, 'myApplications']);
    Route::put('/applications/{application}/withdraw', [ApplicationController::class, 'withdraw']);

    // Notifications routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // Clubs routes
    Route::get('/clubs', [ClubController::class, 'index']);
    Route::get('/clubs/{club}', [ClubController::class, 'show']);
    Route::get('/my-clubs', [ClubController::class, 'myClubs']);
    // search clubs by name or description
    Route::post('/clubs/{club}/join', [ClubController::class, 'joinClub']);
    Route::post('/clubs/{club}/leave', [ClubController::class, 'leaveClub']);

    // Download routes
    Route::get('/resources/download/{type}/{filename}', [DownloadController::class, 'download']);

    // AI Quiz routes
    Route::get('/resources/{resourceId}/generate-quiz', [AIQuizController::class, 'generate']);
    Route::get('/resources/{resourceId}/generate-summary', [AIQuizController::class, 'generateSummary']);
});
