<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers middleware (SDD §6.2).
 *
 * Adds CSP, X-Frame-Options, X-Content-Type-Options, and other
 * security headers to every response. TLS/HSTS is enforced at the
 * Nginx/Caddy reverse proxy layer, not here.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content Security Policy — restrictive baseline.
        // Adjust script-src and style-src as Livewire/Tailwind requires.
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; "
            . "script-src 'self' 'unsafe-inline' 'unsafe-eval'; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com; "
            . "img-src 'self' data:; "
            . "connect-src 'self'; "
            . "frame-ancestors 'self';"
        );

        return $response;
    }
}
