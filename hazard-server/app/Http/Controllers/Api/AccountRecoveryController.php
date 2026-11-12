<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountMfa;
use App\Services\AccountRecovery;
use App\Services\SecurityEvents;
use App\Services\SecuritySessions;
use App\Support\PasswordInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AccountRecoveryController extends Controller
{
    public function options(AccountRecovery $recovery): array
    {
        return ['recovery_available' => $recovery->available()];
    }

    public function requestReset(Request $request, AccountRecovery $recovery): array
    {
        $data = $request->validate(['email' => 'required|string|email|max:255']);
        $recovery->request($data['email'], 'reset', $request);

        return ['message' => 'If this account is eligible for recovery, a link will be sent to its verified email. Otherwise contact municipal support.'];
    }

    public function verifyEmail(Request $request, AccountRecovery $recovery, SecuritySessions $sessions): array
    {
        if ($request->user()->role === 'admin') {
            $sessions->requireRecent($request);
        }
        $recovery->request($request->user()->email, 'verify', $request);

        return ['message' => 'If email delivery is enabled, a verification link will be sent to your account email.'];
    }

    public function consume(Request $request, AccountRecovery $recovery): array
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'regex:/^[a-f0-9-]{36}\.[A-Za-z0-9]{64}$/D'],
            'purpose' => 'required|in:reset,verify',
            'password' => ['nullable', 'required_if:purpose,reset', 'confirmed', ...PasswordInput::rules(true)],
        ]);
        $recovery->consume($data['token'], $data['purpose'], $data['password'] ?? null, $request);

        return ['message' => $data['purpose'] === 'verify' ? 'Email verified. You can return to your profile.' : 'Password changed. Sign in again; administrator MFA remains required.'];
    }

    public function codeReset(Request $request, AccountMfa $mfa, SecuritySessions $sessions): array
    {
        $data = $request->validate([
            'email' => 'required|string|email|max:255',
            'code' => ['required', 'string', 'regex:/^[A-Za-z0-9]{10}-[A-Za-z0-9]{10}$/D'],
            'password' => ['required', 'confirmed', ...PasswordInput::rules(true)],
        ]);
        $key = 'password-code:'.hash('sha256', strtolower(trim($data['email'])));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['code' => 'Recovery could not be completed. Check your details or try again later.']);
        }
        RateLimiter::hit($key, 900);
        $valid = DB::transaction(function () use ($request, $data, $mfa, $sessions) {
            $user = User::where('email', strtolower(trim($data['email'])))->lockForUpdate()->first();
            if (! $user || ! $user->is_active || ! $user->has_password || ! $mfa->verify($user, $data['code'])) {
                return false;
            }
            $user->password = $data['password'];
            $sessions->invalidate($user);
            app(SecurityEvents::class)->record('password_recovered_with_code', $user, $request);

            return true;
        }, 3);
        if (! $valid) {
            throw ValidationException::withMessages(['code' => 'Recovery could not be completed. Check your details or try again later.']);
        }
        RateLimiter::clear($key);

        return ['message' => 'Password changed. The recovery code is used and previous sessions are signed out. Sign in again; administrator 2FA is still required.'];
    }
}
