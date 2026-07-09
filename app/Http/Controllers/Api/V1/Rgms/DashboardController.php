<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rgms;

use App\Http\Controllers\Controller;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Services\DashboardService;
use Illuminate\Http\JsonResponse;

/**
 * DashboardController
 *
 * REST controller for the Dashboard Management module — converted
 * from the SDD's Livewire design (SDD §3.13) to a JSON API for
 * consistency with the rest of RGMS (per your decision).
 *
 * SDD Reference: RGMS SDD §3.13.2
 */
final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $service,
    ) {
    }

    /**
     * GET /api/v1/dashboard/pi/{gid}
     */
    public function pi(ResearchGroup $research_group): JsonResponse
    {
        $this->authorize('viewPiDashboard', $research_group);

        return response()->json(['data' => $this->service->getPiDashboard($research_group)]);
    }

    /**
     * GET /api/v1/dashboard/admin
     */
    public function admin(): JsonResponse
    {
        $this->authorize('viewAdminDashboard', ResearchGroup::class);

        return response()->json(['data' => $this->service->getAdminDashboard()]);
    }

    /**
     * GET /api/v1/dashboard/auditor
     */
    public function auditor(): JsonResponse
    {
        $this->authorize('viewAuditorDashboard', ResearchGroup::class);

        return response()->json(['data' => $this->service->getAuditorDashboard()]);
    }

    /**
     * GET /api/v1/dashboard/sysadmin
     */
    public function sysAdmin(): JsonResponse
    {
        $this->authorize('viewSysAdminDashboard', ResearchGroup::class);

        return response()->json(['data' => $this->service->getSysAdminDashboard()]);
    }
}