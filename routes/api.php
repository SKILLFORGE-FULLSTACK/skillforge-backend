<?php

use App\Http\Controllers\Api\Admin\InterviewCategoryController as AdminInterviewCategoryController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\OAuthController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Certification\BadgeController;
use App\Http\Controllers\Api\Certification\CertificationController;
use App\Http\Controllers\Api\Certification\SubmissionController;
use App\Http\Controllers\Api\Community\ChallengeController;
use App\Http\Controllers\Api\Community\ForumController;
use App\Http\Controllers\Api\Community\NotificationController;
use App\Http\Controllers\Api\Interview\InterviewCategoryController;
use App\Http\Controllers\Api\Interview\InterviewSessionController;
use App\Http\Controllers\Api\Interview\VoiceInterviewController;
use App\Http\Controllers\Api\Jobs\JobPostingController;
use App\Http\Controllers\Api\Marketplace\ContactController;
use App\Http\Controllers\Api\Marketplace\DeveloperSearchController;
use App\Http\Controllers\Api\Marketplace\SavedProfileController;
use App\Http\Controllers\Api\Progression\ActivityController;
use App\Http\Controllers\Api\Progression\LeaderboardController;
use App\Http\Controllers\Api\Progression\StatsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ─── AUTH PUBLIC ────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register', RegisterController::class);
    Route::post('login', LoginController::class);

    // OAuth (Google / GitHub)
    Route::get('{provider}/redirect', [OAuthController::class, 'redirect'])
        ->whereIn('provider', ['google', 'github']);
    Route::get('{provider}/callback', [OAuthController::class, 'callback'])
        ->whereIn('provider', ['google', 'github']);
});

// ─── PUBLIC ─────────────────────────────────────────────────────
Route::get('badges/verify/{token}', [BadgeController::class, 'verify']);

// ─── ROUTES PROTÉGÉES ───────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Me
    Route::get('me', function (Request $request) {
        return response()->json([
            'user' => $request->user()->load([
                'developerProfile',
                'recruiterProfile',
                'badges',
                'skills',
            ]),
        ]);
    });

    // Logout
    Route::post('auth/logout', LogoutController::class);

    // ─── MARKETPLACE ────────────────────────────────────────────────
    Route::prefix('marketplace')->group(function () {
        Route::get('developers', [DeveloperSearchController::class, 'index']);
        Route::get('developers/{username}', [DeveloperSearchController::class, 'show']);
        Route::post('contact', [ContactController::class, 'store']);
        Route::get('contacts', [ContactController::class, 'index']);
        Route::get('saved', [SavedProfileController::class, 'index']);
        Route::post('saved/{developerId}', [SavedProfileController::class, 'store']);
        Route::delete('saved/{developerId}', [SavedProfileController::class, 'destroy']);
    });

    // ─── JOBS ───────────────────────────────────────────────────────
    Route::apiResource('jobs', JobPostingController::class)->only([
        'index',
        'store',
        'show',
        'update',
    ]);

    // ─── ENTRETIENS VOCAUX (IA) ───────────────────────────────────
    // Enregistrée avant "interviews/{id}" pour éviter toute ambiguïté de route.
    Route::prefix('interviews/voice')->group(function () {
        Route::get('/', [VoiceInterviewController::class, 'index']);
        Route::post('start', [VoiceInterviewController::class, 'start']);
        Route::post('{id}/turn', [VoiceInterviewController::class, 'turn']);
        Route::post('{id}/complete', [VoiceInterviewController::class, 'complete']);
        Route::get('{id}', [VoiceInterviewController::class, 'show']);
    });

    // ─── INTERVIEWS ─────────────────────────────────────────────
    Route::prefix('interviews')->group(function () {
        // "categories" avant "{id}" pour éviter toute ambiguïté de route.
        Route::get('categories', [InterviewCategoryController::class, 'index']);
        Route::get('/', [InterviewSessionController::class, 'index']);
        Route::post('start', [InterviewSessionController::class, 'start']);
        Route::post('{id}/respond', [InterviewSessionController::class, 'respond']);
        Route::post('{id}/hint', [InterviewSessionController::class, 'hint']);
        Route::post('{id}/complete', [InterviewSessionController::class, 'complete']);
        Route::get('{id}/report', [InterviewSessionController::class, 'report']);
        Route::get('{id}', [InterviewSessionController::class, 'show']);
    });

    // ─── CERTIFICATIONS ──────────────────────────────────────────
    Route::prefix('certifications')->group(function () {
        Route::get('/', [CertificationController::class, 'index']);
        Route::get('my-submissions', [CertificationController::class, 'mySubmissions']);
        Route::get('{slug}', [CertificationController::class, 'show']);
        Route::post('{slug}/start', [CertificationController::class, 'start']);
    });

    // ─── SOUMISSIONS ─────────────────────────────────────────────
    Route::prefix('submissions')->group(function () {
        Route::post('{id}/submit', [SubmissionController::class, 'submit']);
        Route::get('{id}/report', [SubmissionController::class, 'report']);
    });

    Route::prefix('me')->group(function () {
        Route::get('stats', [StatsController::class, 'myStats']);
        Route::post('streak', [StatsController::class, 'updateStreak']);
        Route::get('xp-history', [StatsController::class, 'xpHistory']);
        Route::get('activity', [ActivityController::class, 'heatmap']);
    });

    // ─── LEADERBOARD ─────────────────────────────────────────────
    Route::get('leaderboard', [LeaderboardController::class, 'index']);

    // ─── FORUM ───────────────────────────────────────────────────
    Route::prefix('forum')->group(function () {
        Route::get('/', [ForumController::class, 'index']);
        Route::post('/', [ForumController::class, 'store']);
        Route::get('{id}', [ForumController::class, 'show']);
        Route::post('{id}/comments', [ForumController::class, 'storeComment']);
        Route::post('{type}/{id}/vote', [ForumController::class, 'vote']);
        Route::post('comments/{commentId}/accept', [ForumController::class, 'acceptAnswer']);
    });

    // ─── NOTIFICATIONS ───────────────────────────────────────────
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('read-all', [NotificationController::class, 'markAllRead']);
        Route::post('{id}/read', [NotificationController::class, 'markRead']);
    });

    // ─── CHALLENGES ──────────────────────────────────────────────
    Route::prefix('challenges')->group(function () {
        Route::get('current', [ChallengeController::class, 'current']);
        Route::post('{id}/submit', [ChallengeController::class, 'submit']);
        Route::get('{id}/leaderboard', [ChallengeController::class, 'leaderboard']);
    });

    // ─── ADMIN ──────────────────────────────────────────────────
    // Autorisation vérifiée dans chaque Controller/FormRequest (isAdmin()).
    Route::prefix('admin')->group(function () {
        Route::prefix('interview-categories')->group(function () {
            Route::get('/', [AdminInterviewCategoryController::class, 'index']);
            Route::post('/', [AdminInterviewCategoryController::class, 'store']);
            Route::put('{id}', [AdminInterviewCategoryController::class, 'update']);
            Route::delete('{id}', [AdminInterviewCategoryController::class, 'destroy']);
        });
    });
});
