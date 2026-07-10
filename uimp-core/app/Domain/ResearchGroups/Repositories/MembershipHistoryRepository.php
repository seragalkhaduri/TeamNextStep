<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Repositories;

use App\Domain\ResearchGroups\Models\MembershipHistory;
use Illuminate\Support\Collection;

/**
 * MembershipHistoryRepository
 *
 * @extends BaseRepository<MembershipHistory>
 */
final class MembershipHistoryRepository extends BaseRepository
{
    public function __construct(MembershipHistory $model)
    {
        parent::__construct($model);
    }

    /**
     * Full change history for a single membership, oldest first.
     */
    public function findByMembership(string $membershipId): Collection
    {
        return $this->model->newQuery()
            ->where('membership_id', $membershipId)
            ->orderBy('changed_at')
            ->get();
    }

    /**
     * Full change history for every membership in a research group,
     * newest first (used by GroupMemberController::memberHistory()).
     */
    public function findByGroup(string $groupId): Collection
    {
        return $this->model->newQuery()
            ->whereHas('groupMembership', function ($query) use ($groupId): void {
                $query->where('group_id', $groupId);
            })
            ->orderByDesc('changed_at')
            ->get();
    }
}