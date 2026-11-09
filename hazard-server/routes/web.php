<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $index = public_path('app/index.html');
    if (is_file($index)) {
        return response()->file($index, ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    return response()->json(['application' => 'Bantay Municipal Hazard API', 'frontend' => 'Run the frontend on port 5173, or package the web assets for a single-origin deployment.']);
});
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1,auth-login');
Route::get('/auth/social/{provider}/redirect', [SocialAuthController::class, 'redirect'])->whereIn('provider', ['google', 'facebook'])->middleware('throttle:20,1,social-browser');
Route::get('/auth/social/{provider}/callback', [SocialAuthController::class, 'callback'])->whereIn('provider', ['google', 'facebook'])->middleware('throttle:20,1,social-browser');
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1,auth-register');
