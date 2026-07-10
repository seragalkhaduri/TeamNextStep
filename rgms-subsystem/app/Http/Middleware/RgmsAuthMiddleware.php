<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\ResearchGroups\Support\RgmsAuthenticatable;
use App\Domain\Auth\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RgmsAuthMiddleware
 *
 * Authentication middleware for all RGMS API routes within the merged
 * UIMP+RGMS application. Since both systems are now unified, we use
 * Sanctum tokens (already issued by UIMP's AuthController) rather than
 * the old JWT-over-HTTP approach.
 *
 * The authenticated UIMP User model is wrapped in RgmsAuthenticatable
 * to expose the same interface that RGMS Policies/Services expect
 * (roles, permissions, preferred_language).
 */
final class RgmsAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Leverage Sanctum's token-based guard
        $user = $request->user('sanctum');

        if (! $user instanceof User) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Wrap the UIMP User into the shape that RGMS services/policies expect
        $rgmsUser = new RgmsAuthenticatable($user);
        $request->merge(['_rgms_user' => $rgmsUser]);

        return $next($request);
    }
}
