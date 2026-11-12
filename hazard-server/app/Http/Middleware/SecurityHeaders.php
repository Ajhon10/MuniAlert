<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // A deployment typo must never expose a production exception trace.
        if (app()->environment('production')) {
            config(['app.debug' => false, 'session.secure' => true, 'session.http_only' => true]);
        }

        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), geolocation=(self), microphone=()');
        if (str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            $policy = "default-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; base-uri 'none'; object-src 'none'; frame-ancestors 'none'; form-action 'self'";
            $file = public_path('app/security-policy.json');
            if (is_file($file) && filesize($file) < 16384) {
                $built = json_decode(file_get_contents($file), true)['policy'] ?? null;
                if (is_string($built) && ! preg_match('/[\r\n]/', $built)) {
                    $policy = $built;
                }
            }
            $response->headers->set('Content-Security-Policy', $policy);
        }
        if ($request->is('api/*', 'auth/*', 'sanctum/*')) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }
        if (app()->environment('production') && $request->isSecure()) {
            // Subdomains/preload require a separate deployment-wide decision.
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
