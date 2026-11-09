<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate(['name' => 'required|string|min:2|max:80', 'email' => 'required|email|max:255|unique:users', 'password' => 'required|string|min:12|max:128']);
        $user = User::create($data);

        return $this->issue($request, $user, 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string|max:128']);

        return DB::transaction(function () use ($request, $data) {
            $user = User::where('email', strtolower(trim($data['email'])))->lockForUpdate()->first();
            if (! $user || ! $user->has_password || ! Hash::check($data['password'], $user->password)) {
                throw ValidationException::withMessages(['email' => 'Email or password is incorrect.']);
            }

            return $this->issue($request, $user);
        });
    }

    private function issue(Request $request, User $user, int $status = 200)
    {
        if ($request->is('api/auth/*')) {
            return response()->json(['user' => $user, 'token' => $user->createToken('mobile', ['*'], now()->addDays(config('hazards.session_days')))->plainTextToken], $status);
        }
        Auth::guard('web')->setRememberDuration(config('hazards.session_days') * 1440)->login($user, true);
        $request->session()->regenerate();

        return response()->json(['user' => $user], $status);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Signed out.']);
    }
}
