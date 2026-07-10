<?php

use App\Domain\ResearchGroups\Exceptions\BlockedDeletionException;
use App\Domain\ResearchGroups\Exceptions\BookingConflictException;
use App\Domain\ResearchGroups\Exceptions\ConflictException;
use App\Domain\ResearchGroups\Exceptions\DuplicateResearchGroupException;
use App\Domain\ResearchGroups\Exceptions\EquipmentUnavailableException;
use App\Domain\ResearchGroups\Exceptions\IneligiblePIException;
use App\Domain\ResearchGroups\Exceptions\InvalidStateTransitionException;
use App\Domain\ResearchGroups\Exceptions\UimpApiException;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Security headers on all responses (SDD §6.2)
        $middleware->append(SecurityHeaders::class);

        // Sanctum stateful middleware for SPA/web sessions
        $middleware->statefulApi();

        $middleware->alias([
            'subsystem.auth' => \App\Http\Middleware\AuthenticateSubsystem::class,
            // RGMS: wraps authenticated UIMP user into RgmsAuthenticatable
            'rgms.auth'      => \App\Http\Middleware\RgmsAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // ── RGMS domain exception handlers ────────────────────────────
        $exceptions->renderable(fn (IneligiblePIException $e): JsonResponse => response()->json([
            'error'   => 'ineligible_pi',
            'message' => $e->getMessage(),
        ], 422));

        $exceptions->renderable(fn (DuplicateResearchGroupException $e): JsonResponse => response()->json([
            'error'       => 'duplicate_group',
            'existing_id' => $e->existingId,
        ], 409));

        $exceptions->renderable(fn (InvalidStateTransitionException $e): JsonResponse => response()->json([
            'error' => 'invalid_transition',
            'from'  => $e->from,
            'to'    => $e->to,
        ], 422));

        $exceptions->renderable(fn (BlockedDeletionException $e): JsonResponse => response()->json([
            'error'  => 'blocked_deletion',
            'reason' => $e->getMessage(),
            'counts' => $e->counts,
        ], 409));

        $exceptions->renderable(fn (EquipmentUnavailableException $e): JsonResponse => response()->json([
            'error'  => 'equipment_unavailable',
            'status' => $e->status,
        ], 409));

        $exceptions->renderable(fn (BookingConflictException $e): JsonResponse => response()->json([
            'error'     => 'booking_conflict',
            'conflicts' => $e->conflicts,
        ], 409));

        $exceptions->renderable(fn (ConflictException $e): JsonResponse => response()->json([
            'error'   => 'conflict',
            'message' => $e->getMessage(),
        ], 409));

        $exceptions->renderable(fn (UimpApiException $e): JsonResponse => response()->json([
            'error'       => 'uimp_unavailable',
            'retry_after' => $e->retryAfter,
        ], 502));
    })->create();
