<?php

namespace App\Http\Middleware;

use App\Services\WebDeviceSessions;
use Closure;
use Illuminate\Http\Request;

class RestoreDeviceLogin
{
    public function handle(Request $request, Closure $next)
    {
        app(WebDeviceSessions::class)->restore($request);

        return $next($request);
    }
}
