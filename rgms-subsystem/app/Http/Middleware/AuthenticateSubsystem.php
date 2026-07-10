<?php

namespace App\Http\Middleware;

use App\Domain\Subsystems\Enums\SubsystemStatus;
use App\Domain\Subsystems\Models\Subsystem;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSubsystem
{
    /**
     * Handle an incoming request.
     *
     * Authenticates external subsystems using the X-API-Key header.
     * If valid, sets the authenticated subsystem in the request attributes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json(['message' => 'API Key required.'], 401);
        }

        $hash = hash('sha256', $apiKey);

        $subsystem = Subsystem::where('api_key_hash', $hash)
            ->where('status', SubsystemStatus::ACTIVE->value)
            ->whereNull('deleted_at')
            ->first();

        if (!$subsystem) {
            return response()->json(['message' => 'Invalid or suspended API Key.'], 401);
        }

        // Store the subsystem on the request for access in controllers and audit logs
        $request->attributes->set('authenticated_subsystem', $subsystem);

        return $next($request);
    }
}
