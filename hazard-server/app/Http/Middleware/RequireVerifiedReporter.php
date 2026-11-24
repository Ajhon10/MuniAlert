<?php

namespace App\Http\Middleware;

use App\Services\AccountOnboarding;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireVerifiedReporter
{
    public function handle(Request $request, Closure $next): Response
    {
        app(AccountOnboarding::class)->requireReporter($request->user());

        return $next($request);
    }
}
