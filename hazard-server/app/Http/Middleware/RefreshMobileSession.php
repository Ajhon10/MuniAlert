<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class RefreshMobileSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();
        $renewUntil = now()->addDays(config('hazards.session_days'));
        // Authentication has already rejected expired/revoked tokens. Renew at
        // most hourly, using UPDATE so concurrent revocation cannot recreate one.
        if ($token instanceof PersonalAccessToken && $token->name === 'mobile'
            && $token->expires_at && $token->expires_at->lt($renewUntil->copy()->subHour())) {
            PersonalAccessToken::whereKey($token->id)->where('expires_at', '>', now())
                ->update(['expires_at' => $renewUntil]);
        }

        return $next($request);
    }
}
