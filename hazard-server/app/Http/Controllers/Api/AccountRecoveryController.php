<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AccountRecovery;
use App\Services\SecuritySessions;
use App\Support\PasswordInput;
use Illuminate\Http\Request;

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
}
