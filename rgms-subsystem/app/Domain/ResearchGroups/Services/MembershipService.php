<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services;

use App\Domain\ResearchGroups\Exceptions\ConflictException;
use App\Domain\ResearchGroups\Jobs\SendUimpNotification;
use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Repositories\GroupMembershipRepository;
use App\Domain\ResearchGroups\Repositories\MembershipHistoryRepository;
use App\Domain\ResearchGroups\Services\Clients\UimpMasterDataClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * MembershipService
 *
 * Implements all business rules for the Membership Management
 * module: UIMP eligibility validation, over-allocation warnings
 * (FR-MEM-011), BR-001 (exactly one active PI per Active group)
 * enforcement, and full membership history tracking.
 *
 * SDD Reference: RGMS SDD §3.2.2, §3.2.7
 */
final class MembershipService
{
    public function __construct(
        private readonly GroupMembershipRepository $repository,
        private readonly MembershipHistoryRepository $historyRepository,
        private readonly UimpMasterDataClient $uimpClient,
    ) {
    }

    /**
     * Add a new member to a research group.
     *
     * @param array<string, mixed> $data
     * @return array{membership: GroupMembership, over_allocation_warning: bool}
     */
    public function addMember(string $groupId, array $data): array
    {
        $staff = $this->uimpClient->getAcademicStaff($data['member_uimp_id']);
        $memberType = $staff !== null ? GroupMembership::MEMBER_TYPE_STAFF : GroupMembership::MEMBER_TYPE_STUDENT;

        $isEligible = $this->uimpClient->validateMemberEligibility(
            $data['member_uimp_id'],
            $memberType,
            $data['role'],
        );

        if (! $isEligible) {
            throw new ConflictException('Member is not eligible for the requested role.');
        }

        $existingWorkload = $this->repository->sumWorkloadForMember($data['member_uimp_id']);
        $overAllocationWarning = ($existingWorkload + $data['workload_percentage']) > 100;

        $membership = DB::transaction(function () use ($groupId, $data, $memberType): GroupMembership {
            $membership = $this->repository->create([
                ...$data,
                'group_id' => $groupId,
                'member_type' => $memberType,
                'status' => GroupMembership::STATUS_ACTIVE,
            ]);

            AuditLog::record('CREATE', 'group_memberships', $membership->id, null, $data);

            dispatch(new SendUimpNotification(
                [$data['member_uimp_id']],
                'membership.created',
                ['group_id' => $groupId, 'role' => $data['role']],
            ));

            return $membership;
        });

        return ['membership' => $membership, 'over_allocation_warning' => $overAllocationWarning];
    }

    /**
     * Update an existing membership's role, dates, or workload.
     *
     * If the role changes away from PI, verifies another active PI
     * exists on the group first (BR-001). Records a membership_history
     * entry before applying any role or status change (SDD §3.2.13).
     *
     * @param array<string, mixed> $data
     */
    public function updateMember(GroupMembership $membership, array $data): GroupMembership
    {
        $roleChangingAwayFromPi = $membership->role === GroupMembership::ROLE_PI
            && isset($data['role'])
            && $data['role'] !== GroupMembership::ROLE_PI;

        if ($roleChangingAwayFromPi) {
            $this->assertAnotherPiExists($membership);
        }

        $previousRole = $membership->role;
        $previousStatus = $membership->status;

        return DB::transaction(function () use ($membership, $data, $previousRole, $previousStatus): GroupMembership {
            if (isset($data['role']) && $data['role'] !== $previousRole) {
                $this->historyRepository->create([
                    'membership_id' => $membership->id,
                    'previous_role' => $previousRole,
                    'new_role' => $data['role'],
                    'previous_status' => $previousStatus,
                    'new_status' => $data['status'] ?? $previousStatus,
                    'change_reason' => $data['change_reason'] ?? null,
                    'changed_by' => (string) Auth::id(),
                    'changed_at' => now(),
                ]);
            }

            $updated = $this->repository->update($membership, $data);

            AuditLog::record(
                'UPDATE',
                'group_memberships',
                $updated->id,
                ['role' => $previousRole, 'status' => $previousStatus],
                $data,
            );

            return $updated;
        });
    }

    /**
     * Terminate a membership: sets end_date to today and status to
     * Inactive. Re-checks BR-001 before completing if the terminated
     * member is a PI.
     */
    public function terminateMember(GroupMembership $membership): GroupMembership
    {
        if ($membership->role === GroupMembership::ROLE_PI) {
            $this->assertAnotherPiExists($membership);
        }

        $previousStatus = $membership->status;

        return DB::transaction(function () use ($membership, $previousStatus): GroupMembership {
            $this->historyRepository->create([
                'membership_id' => $membership->id,
                'previous_role' => $membership->role,
                'new_role' => $membership->role,
                'previous_status' => $previousStatus,
                'new_status' => GroupMembership::STATUS_INACTIVE,
                'change_reason' => 'Membership terminated',
                'changed_by' => (string) Auth::id(),
                'changed_at' => now(),
            ]);

            $terminated = $this->repository->update($membership, [
                'end_date' => now()->toDateString(),
                'status' => GroupMembership::STATUS_INACTIVE,
            ]);

            AuditLog::record(
                'UPDATE',
                'group_memberships',
                $terminated->id,
                ['status' => $previousStatus],
                ['status' => GroupMembership::STATUS_INACTIVE, 'end_date' => $terminated->end_date],
            );

            dispatch(new SendUimpNotification(
                [$terminated->member_uimp_id],
                'membership.terminated',
                ['group_id' => $terminated->group_id],
            ));

            return $terminated;
        });
    }

    /**
     * Assert that at least one other active PI membership exists on
     * the same group besides the one being changed/terminated
     * (BR-001: exactly one PI per Active group at all times).
     */
    private function assertAnotherPiExists(GroupMembership $membership): void
    {
        $currentPi = $this->repository->findPIForGroup($membership->group_id);

        if ($currentPi === null || $currentPi->id === $membership->id) {
            throw new ConflictException(
                'BR-001 violation: cannot remove or reassign the sole PI without a replacement.',
            );
        }
    }
}