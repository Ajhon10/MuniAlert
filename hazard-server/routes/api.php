<?php

use App\Http\Controllers\Api\AccountPreferencesController;
use App\Http\Controllers\Api\AccountRecoveryController;
use App\Http\Controllers\Api\AccountSecurityController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\DashboardBackgroundController;
use App\Http\Controllers\Api\EvidenceController;
use App\Http\Controllers\Api\ImageReviewController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReportPublicationController;
use App\Http\Controllers\Api\ReportVerificationController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Middleware\RefreshMobileSession;
use App\Http\Middleware\RequireSecureAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:120,1,api-global')->group(function () {
    Route::get('health', function () {
        try {
            DB::select('SELECT 1');
            $response = response()->json(['status' => 'ok']);
        } catch (Throwable $error) {
            report($error);
            $response = response()->json(['status' => 'unavailable'], 503);
        }
        if (app()->environment('local')) {
            $workspace = strtolower(str_replace('\\', '/', dirname(base_path())));
            $response->header('X-Bantay-Dev', substr(hash('sha256', $workspace), 0, 16));
        }

        return $response;
    });
    Route::get('catalog', [ReportController::class, 'catalog']);
    Route::get('auth/providers', [SocialAuthController::class, 'providers']);
    Route::get('auth/security-options', [AccountRecoveryController::class, 'options']);
    Route::post('auth/recovery', [AccountRecoveryController::class, 'requestReset'])->middleware('throttle:5,1,recovery-request');
    Route::post('auth/recovery/consume', [AccountRecoveryController::class, 'consume'])->middleware('throttle:10,1,recovery-consume');
    Route::post('auth/social/{provider}/attempts', [SocialAuthController::class, 'prepare'])->whereIn('provider', ['google', 'facebook'])->middleware('throttle:10,1,social-prepare');
    Route::post('auth/social/exchange', [SocialAuthController::class, 'exchange'])->middleware('throttle:10,1,social-exchange');
    Route::get('stats', [ReportController::class, 'stats']);
    Route::get('reports/nearby', [ReportController::class, 'nearby']);
    Route::get('reports', [ReportController::class, 'index']);
    Route::get('settings', [CommunityController::class, 'settings']);
    Route::get('dashboard-background/{id}', [DashboardBackgroundController::class, 'show'])->whereUuid('id');
    Route::get('badges', [CommunityController::class, 'badges']);
    Route::get('news', [CommunityController::class, 'news']);
    Route::get('news/{report}/comments', [CommunityController::class, 'comments']);
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1,auth-login');
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1,auth-register');
    Route::post('auth/mfa/verify', [AccountSecurityController::class, 'verify'])->middleware('throttle:10,1,mfa-verify');
    Route::middleware(['auth:sanctum', RequireSecureAccount::class, RefreshMobileSession::class])->group(function () {
        Route::get('security/status', [AccountSecurityController::class, 'status']);
        Route::post('security/verify-email', [AccountRecoveryController::class, 'verifyEmail'])->middleware('throttle:3,1,email-verification');
        Route::post('security/reauthenticate', [AccountSecurityController::class, 'reauthenticate'])->middleware('throttle:5,1,security-reauth');
        Route::post('security/recovery-codes', [AccountSecurityController::class, 'recoveryCodes'])->middleware('throttle:5,1,security-recovery-codes');
        Route::get('auth/me', fn (Request $r) => $r->user());
        Route::get('auth/social/accounts', [SocialAuthController::class, 'accounts']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('profile', [CommunityController::class, 'profile']);
        Route::put('preferences/reading', [AccountPreferencesController::class, 'reading']);
        Route::put('preferences/email', [AccountPreferencesController::class, 'email']);
        Route::patch('profile', [CommunityController::class, 'updateProfile']);
        Route::put('admin/settings', [CommunityController::class, 'saveSettings']);
        Route::post('admin/dashboard-background', [DashboardBackgroundController::class, 'store'])->middleware('throttle:5,1,branding-upload');
        Route::delete('admin/dashboard-background', [DashboardBackgroundController::class, 'destroy']);
        Route::patch('admin/badges/{badge}', [CommunityController::class, 'saveBadge']);
        Route::get('reports/mine', [ReportController::class, 'index'])->name('reports.mine');
        Route::get('admin/reports', [ReportController::class, 'index'])->name('reports.admin');
        Route::get('admin/analytics', [AnalyticsController::class, 'index']);
        Route::get('admin/stats', [ReportController::class, 'stats'])->name('reports.admin.stats');
        Route::post('admin/reports/{report}/verify', [ReportVerificationController::class, 'store'])->name('reports.verify');
        Route::post('admin/reports/{report}/photos/{photo}/review', [ImageReviewController::class, 'store'])->whereUuid('photo');
        Route::post('admin/reports/{report}/post', [ReportPublicationController::class, 'store'])->name('reports.publish');
        Route::delete('admin/reports/{report}/post', [ReportPublicationController::class, 'destroy'])->name('reports.unpublish');
        Route::post('reports', [ReportController::class, 'store'])->middleware('throttle:10,1,report-submit')->name('reports.store');
        Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::patch('reports/{report}', [ReportController::class, 'update'])->name('reports.update');
        Route::get('reports/{report}/photos/{photo}', [EvidenceController::class, 'show']);
        Route::post('reports/{report}/resolution-photos', [EvidenceController::class, 'store'])->middleware('throttle:10,1,resolution-upload');
        Route::post('reports/{report}/confirm', [ReportController::class, 'confirm']);
        Route::delete('reports/{report}', [ReportController::class, 'destroy']);
        Route::post('news/{report}/comments', [CommunityController::class, 'comment'])->middleware('throttle:20,1,news-comment');
        Route::delete('news/{report}/comments/{comment}', [CommunityController::class, 'deleteComment']);
        Route::post('news/{report}/reactions', [CommunityController::class, 'react'])->middleware('throttle:30,1,news-reaction');
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::patch('notifications/read', [NotificationController::class, 'read']);
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'readOne'])->whereUuid('notification');
        Route::post('devices', [NotificationController::class, 'device']);
        Route::delete('devices', [NotificationController::class, 'removeDevice']);
    });
});
