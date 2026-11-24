<?php

namespace App\Http\Middleware;

use App\Services\AccountOnboarding;
use App\Services\SecuritySessions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSecureAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessions = app(SecuritySessions::class);
        if (! $sessions->valid($request, $request->user())) {
            return response()->json(['message' => 'Your account or security settings changed. Sign in again.'], 401);
        }
        if ($request->user()->role === 'admin'
            && ! $request->is('api/auth/me', 'api/auth/logout', 'api/security/*', 'api/account/verification', 'api/account/policies', 'api/account/identity', 'api/profile', 'api/preferences/reading', 'api/preferences/email', 'api/devices')
            && ! app(AccountOnboarding::class)->state($request->user())['can_administer']) {
            return response()->json(['message' => 'Verify your email and accept the current policies in Settings before accessing municipal administration. ID verification is optional.',
                'account_verification_required' => true, 'account_id' => $request->user()->id], 403);
        }
        if ($request->user()->role === 'admin' && ! $request->isMethodSafe()
            && ! $request->is('api/auth/logout', 'api/security/reauthenticate', 'api/devices', 'api/preferences/reading', 'api/preferences/email', 'api/notifications/read', 'api/notifications/*/read')) {
            $sessions->requireRecent($request);
        }

        return $next($request);
    }
}
