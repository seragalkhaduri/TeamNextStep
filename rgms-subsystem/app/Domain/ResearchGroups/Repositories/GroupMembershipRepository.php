<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Repositories;

use App\Domain\ResearchGroups\Models\GroupMembership;
use Illuminate\Support\Collection;

/**
 * GroupMembershipRepository
 *
 * @extends BaseRepository<GroupMembership>
 */
final class GroupMembershipRepository extends BaseRepository
{
    public function __construct(GroupMembership $model)
    {
        parent::__construct($model);
    }

    /**
     * Active memberships for a research group, ordered by role.
     */
    public function findActiveByGroup(string $groupId): Collection
    {
        return $this->model->newQuery()
            ->where('group_id', $groupId)
            ->where('status', GroupMembership::STATUS_ACTIVE)
            ->orderBy('role')
            ->get();
    }

    /**
     * All groups a member is currently active in, across the entire
     * platform.
     */
    public function findByMemberAcrossGroups(string $memberId): Collection
    {
        return $this->model->newQuery()
            ->where('member_uimp_id', $memberId)
            ->where('status', GroupMembership::STATUS_ACTIVE)
            ->get();
    }

    /**
     * Sum of workload_percentage across all of a member's active
     * memberships (platform-wide), used for over-allocation checks.
     */
    public function sumWorkloadForMember(string $memberId): int
    {
        return (int) $this->model->newQuery()
            ->where('member_uimp_id', $memberId)
            ->where('status', GroupMembership::STATUS_ACTIVE)
            ->sum('workload_percentage');
    }
/**
     * Find a specific member's active membership record within a
     * specific group (SDD §3.6.5 —
     * validateAuthorsAreMemberOfGroup()).
     */
    public function findByMemberAndGroup(string $memberId, string $groupId): ?GroupMembership
    {
        return $this->model->newQuery()
            ->where('group_id', $groupId)
            ->where('member_uimp_id', $memberId)
            ->where('status', GroupMembership::STATUS_ACTIVE)
            ->first();
    }
    /**
     * The current PI membership record for a research group, if one
     * exists — used for BR-001 enforcement (a group must always have
     * exactly one active PI).
     */
    public function findPIForGroup(string $groupId): ?GroupMembership
    {
        return $this->model->newQuery()
            ->where('group_id', $groupId)
            ->where('role', GroupMembership::ROLE_PI)
            ->where('status', GroupMembership::STATUS_ACTIVE)
            ->first();
    }
}