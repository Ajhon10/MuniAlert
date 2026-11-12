<?php

use App\Http\Controllers\Api\AccountSecurityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\NewsShareController;
use Illuminate\Support\Facades\Route;

Route::get('/news/{report}', [NewsShareController::class, 'show'])->whereUuid('report')->middleware('throttle:120,1,news-share');

Route::get('/', function () {
    $index = public_path('app/index.html');
    if (is_file($index)) {
        return response()->file($index, ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    return response()->json(['application' => 'Bantay Municipal Hazard API', 'frontend' => 'Run the frontend on port 5173, or package the web assets for a single-origin deployment.']);
});
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1,auth-login');
Route::post('/auth/mfa/verify', [AccountSecurityController::class, 'verify'])->middleware('throttle:10,1,mfa-verify');
Route::get('/auth/mfa/pending', [AccountSecurityController::class, 'pending'])->middleware('throttle:10,1,mfa-pending');
Route::get('/auth/social/{provider}/redirect', [SocialAuthController::class, 'redirect'])->whereIn('provider', ['google', 'facebook'])->middleware('throttle:20,1,social-browser');
Route::get('/auth/social/{provider}/callback', [SocialAuthController::class, 'callback'])->whereIn('provider', ['google', 'facebook'])->middleware('throttle:20,1,social-browser');
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1,auth-register');
