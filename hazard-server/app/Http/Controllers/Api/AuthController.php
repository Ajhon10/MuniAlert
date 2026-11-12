<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountMfa;
use App\Services\SecurityEvents;
use App\Services\WebDeviceSessions;
use App\Support\PasswordInput;
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
        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }
        $data = $request->validate(['name' => 'required|string|min:2|max:80', 'email' => 'required|string|email|max:255|unique:users', 'password' => ['required', ...PasswordInput::rules(true)]]);
        $user = User::create($data);

        return $this->issue($request, $user, 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|string|email|max:255', 'password' => ['required', ...PasswordInput::rules()]]);

        $result = DB::transaction(function () use ($request, $data) {
            $user = User::where('email', strtolower(trim($data['email'])))->lockForUpdate()->first();
            if (! $user || ! $user->is_active || ! $user->has_password || ! Hash::check($data['password'], $user->password)) {
                return null;
            }

            return $this->issue($request, $user);
        });
        if (! $result) {
            app(SecurityEvents::class)->record('login_failed', null, $request);
            throw ValidationException::withMessages(['email' => 'Email or password is incorrect.']);
        }

        return $result;
    }

    private function issue(Request $request, User $user, int $status = 200)
    {
        return response()->json(app(AccountMfa::class)->login($request, $user), $status);
    }

    public function logout(Request $request)
    {
        app(SecurityEvents::class)->record('logout', $request->user(), $request);
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
        if ($request->hasSession()) {
            app(WebDeviceSessions::class)->revokeCurrent($request);
            Auth::guard('web')->logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Signed out.']);
    }
}
