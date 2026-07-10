<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rgms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rgms\LogMaintenanceRequest;
use App\Http\Requests\Rgms\StoreEquipmentRequest;
use App\Http\Requests\Rgms\TransitionEquipmentStatusRequest;
use App\Http\Requests\Rgms\UpdateEquipmentRequest;
use App\Http\Resources\Rgms\EquipmentResource;
use App\Http\Resources\Rgms\MaintenanceRecordResource;
use App\Domain\ResearchGroups\Models\ResearchEquipment;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Repositories\EquipmentMaintenanceRepository;
use App\Domain\ResearchGroups\Repositories\EquipmentRepository;
use App\Domain\ResearchGroups\Services\EquipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * EquipmentController
 *
 * Thin controller for the Research Equipment Management module.
 *
 * SDD Reference: RGMS SDD §3.8.3, §3.8.4
 */
final class EquipmentController extends Controller
{
    public function __construct(
        private readonly EquipmentService $service,
        private readonly EquipmentRepository $repository,
        private readonly EquipmentMaintenanceRepository $maintenanceRepository,
    ) {
    }

    /**
     * GET /api/v1/research-groups/{gid}/equipment
     */
    public function index(Request $request, ResearchGroup $research_group): JsonResponse
    {
        $this->authorize('view', $research_group);

        $filters = $request->only(['category', 'status', 'laboratory_ref_id']);
        $perPage = (int) $request->integer('per_page', 15);

        $equipment = $this->repository->findByGroup($research_group->id, $filters, $perPage);

        return response()->json([
            'data' => EquipmentResource::collection($equipment),
            'meta' => [
                'total' => $equipment->total(),
                'per_page' => $equipment->perPage(),
                'current_page' => $equipment->currentPage(),
                'last_page' => $equipment->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/research-groups/{gid}/equipment
     */
    public function store(StoreEquipmentRequest $request, ResearchGroup $research_group): JsonResponse
    {
        $equipment = $this->service->create($research_group->id, $request->validated());

        return EquipmentResource::make($equipment)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/equipment/{id}
     */
    public function show(ResearchEquipment $equipment): EquipmentResource
    {
        $this->authorize('view', $equipment);

        return EquipmentResource::make($equipment);
    }

    /**
     * PUT /api/v1/equipment/{id}
     */
    public function update(UpdateEquipmentRequest $request, ResearchEquipment $equipment): EquipmentResource
    {
        $updated = $this->service->update($equipment, $request->validated());

        return EquipmentResource::make($updated);
    }

    /**
     * PATCH /api/v1/equipment/{id}/status
     */
    public function transitionStatus(TransitionEquipmentStatusRequest $request, ResearchEquipment $equipment): EquipmentResource
    {
        $updated = $this->service->transitionStatus($equipment, $request->validated('status'));

        return EquipmentResource::make($updated);
    }

    /**
     * POST /api/v1/equipment/{id}/maintenance
     */
    public function logMaintenance(LogMaintenanceRequest $request, ResearchEquipment $equipment): JsonResponse
    {
        $record = $this->service->logMaintenance($equipment, $request->validated());

        return MaintenanceRecordResource::make($record)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/equipment/{id}/maintenance
     */
    public function maintenanceLog(ResearchEquipment $equipment): JsonResponse
    {
        $this->authorize('view', $equipment);

        $records = $this->maintenanceRepository->findByEquipment($equipment->id);

        return response()->json([
            'data' => MaintenanceRecordResource::collection($records),
        ]);
    }

    /**
     * DELETE /api/v1/equipment/{id}
     */
    public function destroy(ResearchEquipment $equipment): Response
    {
        $this->authorize('delete', $equipment);

        $this->service->softDelete($equipment);

        return response()->noContent();
    }

    /**
     * GET /api/v1/equipment
     */
    public function globalIndex(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ResearchEquipment::class);

        $filters = $request->only(['category', 'status', 'laboratory_ref_id']);
        $perPage = (int) $request->integer('per_page', 15);

        $equipment = $this->repository->paginateGlobal($filters, $perPage);

        return response()->json([
            'data' => EquipmentResource::collection($equipment),
            'meta' => [
                'total' => $equipment->total(),
                'per_page' => $equipment->perPage(),
                'current_page' => $equipment->currentPage(),
                'last_page' => $equipment->lastPage(),
            ],
        ]);
    }
}