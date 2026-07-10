<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services;
use Illuminate\Support\Facades\Cache;
use App\Domain\ResearchGroups\Exceptions\ConflictException;
use App\Domain\ResearchGroups\Exceptions\InvalidStateTransitionException;
use App\Domain\ResearchGroups\Jobs\SendUimpNotification;
use App\Domain\ResearchGroups\Models\Patent;
use App\Domain\ResearchGroups\Models\PatentInventor;
use App\Domain\ResearchGroups\Repositories\GroupMembershipRepository;
use App\Domain\ResearchGroups\Repositories\PatentRepository;
use Illuminate\Support\Facades\DB;

/**
 * PatentService
 *
 * Implements all business rules for the Patents Management module:
 * registration with inventor-membership validation, and the literal
 * lifecycle state machine from SDD §3.7.5.
 *
 * SDD Reference: RGMS SDD §3.7.5, §3.7.6
 */
final class PatentService
{
    /**
     * Literal state machine table (SDD §3.7.5).
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED_TRANSITIONS = [
        Patent::STATUS_FILED => [Patent::STATUS_UNDER_EXAMINATION, Patent::STATUS_REJECTED],
        Patent::STATUS_UNDER_EXAMINATION => [Patent::STATUS_GRANTED, Patent::STATUS_REJECTED],
        Patent::STATUS_GRANTED => [Patent::STATUS_EXPIRED],
        Patent::STATUS_REJECTED => [],
        Patent::STATUS_EXPIRED => [],
    ];

    public function __construct(
        private readonly PatentRepository $repository,
        private readonly GroupMembershipRepository $membershipRepository,
    ) {
    }

    /**
     * Register a new patent with its ordered inventors.
     *
     * @param array<string, mixed> $data
     * @param list<string> $inventorUimpIds
     */
    public function register(string $groupId, array $data, array $inventorUimpIds): Patent
    {
        $this->validateInventors($groupId, $inventorUimpIds);

        return DB::transaction(function () use ($groupId, $data, $inventorUimpIds): Patent {
            $patent = $this->repository->create([
                ...$data,
                'research_group_id' => $groupId,
                'status' => Patent::STATUS_FILED,
            ]);

            foreach ($inventorUimpIds as $order => $memberUimpId) {
                PatentInventor::create([
                    'patent_id' => $patent->id,
                    'member_uimp_id' => $memberUimpId,
                    'inventor_order' => $order + 1,
                ]);
            }

            AuditLog::record('CREATE', 'patents', $patent->id, null, $data);
Cache::tags(['analytics:patents'])->flush();
            return $patent;
        });
    }

    /**
     * Update mutable attributes of a patent (status is excluded —
     * see transition()).
     *
     * @param array<string, mixed> $data
     */
    public function update(Patent $patent, array $data): Patent
    {
        $oldValues = $patent->only(array_keys($data));

        return DB::transaction(function () use ($patent, $data, $oldValues): Patent {
            $updated = $this->repository->update($patent, $data);

            AuditLog::record('UPDATE', 'patents', $updated->id, $oldValues, $data);
Cache::tags(['analytics:patents'])->flush();
            return $updated;
        });
    }

    /**
     * Transition a patent's status per the literal state machine
     * (SDD §3.7.5). On Granted, records grant_date if not already
     * set. On any terminal state (Rejected/Expired), notifies the PI.
     */
    public function transition(Patent $patent, string $newStatus): Patent
    {
        $currentStatus = $patent->status;

        if (! in_array($newStatus, self::ALLOWED_TRANSITIONS[$currentStatus] ?? [], true)) {
            throw new InvalidStateTransitionException(
                sprintf('Cannot transition patent from %s to %s.', $currentStatus, $newStatus),
                from: $currentStatus,
                to: $newStatus,
            );
        }

        return DB::transaction(function () use ($patent, $currentStatus, $newStatus): Patent {
            $data = ['status' => $newStatus];

            if ($newStatus === Patent::STATUS_GRANTED && $patent->grant_date === null) {
                $data['grant_date'] = now()->toDateString();
            }

            $updated = $this->repository->update($patent, $data);

            AuditLog::record(
                'TRANSITION',
                'patents',
                $updated->id,
                ['status' => $currentStatus],
                $data,
            );

            if (in_array($newStatus, [Patent::STATUS_REJECTED, Patent::STATUS_EXPIRED], true)) {
                dispatch(new SendUimpNotification(
                    [$updated->researchGroup->pi_staff_id],
                    'patent.status_changed',
                    ['entity_id' => $updated->id, 'new_status' => $newStatus],
                ));
            }
Cache::tags(['analytics:patents'])->flush();
            return $updated;
        });
    }

    /**
     * Soft delete a patent.
     */
    public function softDelete(Patent $patent): bool
    {
        return DB::transaction(function () use ($patent): bool {
            $result = $this->repository->softDelete($patent);

            AuditLog::record('DELETE', 'patents', $patent->id, $patent->toArray(), null);

            return $result;
        });
    }

    /**
     * Verify each submitted inventor is an active member of the
     * owning research group (FR-PUB-008), same pattern as
     * PublicationService::validateAuthorsAreMemberOfGroup().
     *
     * @param list<string> $inventorUimpIds
     */
    private function validateInventors(string $groupId, array $inventorUimpIds): void
    {
        foreach ($inventorUimpIds as $memberUimpId) {
            if ($this->membershipRepository->findByMemberAndGroup($memberUimpId, $groupId) === null) {
                throw new ConflictException(
                    "UIMP member {$memberUimpId} is not an active member of this research group.",
                );
            }
        }
    }
}