<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Rgms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rgms\StoreMembershipRequest;
use App\Http\Requests\Rgms\UpdateMembershipRequest;
use App\Http\Resources\Rgms\GroupMemberResource;
use App\Http\Resources\Rgms\MemberHistoryResource;
use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Repositories\GroupMembershipRepository;
use App\Domain\ResearchGroups\Repositories\MembershipHistoryRepository;
use App\Domain\ResearchGroups\Services\MembershipService;
use App\Domain\ResearchGroups\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * GroupMemberController
 *
 * Thin controller for the Membership Management module, scoped to
 * its parent ResearchGroup via route-model binding. No business
 * logic, no direct database queries, no validation logic.
 *
 * SDD Reference: RGMS SDD §3.2.4, §3.2.6
 */
final class GroupMemberController extends Controller
{
    public function __construct(
        private readonly MembershipService $service,
        private readonly GroupMembershipRepository $repository,
        private readonly MembershipHistoryRepository $historyRepository,
        private readonly ReportService $reportService,
    ) {
    }

    /**
     * GET /api/v1/research-groups/{gid}/members
     */
    public function index(ResearchGroup $research_group): JsonResponse
    {
        $this->authorize('view', $research_group);

        $memberships = $this->repository->findActiveByGroup($research_group->id);

        return response()->json([
            'data' => GroupMemberResource::collection($memberships),
        ]);
    }

    /**
     * POST /api/v1/research-groups/{gid}/members
     */
    public function store(StoreMembershipRequest $request, ResearchGroup $research_group): JsonResponse
    {
        $result = $this->service->addMember($research_group->id, $request->validated());

        return GroupMemberResource::make($result['membership'])
            ->additional(['over_allocation_warning' => $result['over_allocation_warning']])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/research-groups/{gid}/members/{mid}
     */
    public function show(ResearchGroup $research_group, GroupMembership $member): GroupMemberResource
    {
        $this->authorize('view', $member);

        return GroupMemberResource::make($member);
    }

    /**
     * PUT /api/v1/research-groups/{gid}/members/{mid}
     */
    public function update(
        UpdateMembershipRequest $request,
        ResearchGroup $research_group,
        GroupMembership $member,
    ): GroupMemberResource {
        $updated = $this->service->updateMember($member, $request->validated());

        return GroupMemberResource::make($updated);
    }

    /**
     * DELETE /api/v1/research-groups/{gid}/members/{mid}
     */
    public function destroy(ResearchGroup $research_group, GroupMembership $member): Response
    {
        $this->authorize('removeMember', $member);

        $this->service->terminateMember($member);

        return response()->noContent();
    }

    /**
     * GET /api/v1/research-groups/{gid}/members/history
     */
    public function memberHistory(ResearchGroup $research_group): JsonResponse
    {
        $this->authorize('view', $research_group);

        $history = $this->historyRepository->findByGroup($research_group->id);

        return response()->json([
            'data' => MemberHistoryResource::collection($history),
        ]);
    }

    /**
     * GET /api/v1/research-groups/{gid}/members/export
     */
    public function exportRoster(Request $request, ResearchGroup $research_group): StreamedResponse
    {
        $this->authorize('export', GroupMembership::class);

        $format = $request->string('format', 'pdf')->toString();

        return $this->reportService->generateRosterReport($research_group, $format);
    }
}