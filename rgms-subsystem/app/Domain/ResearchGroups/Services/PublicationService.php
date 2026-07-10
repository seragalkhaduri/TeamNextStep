<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services;
use Illuminate\Support\Facades\Cache;
use App\Domain\ResearchGroups\Exceptions\ConflictException;
use App\Domain\ResearchGroups\Exceptions\InvalidStateTransitionException;
use App\Domain\ResearchGroups\Models\Publication;
use App\Domain\ResearchGroups\Models\PublicationAuthor;
use App\Domain\ResearchGroups\Repositories\GroupMembershipRepository;
use App\Domain\ResearchGroups\Repositories\PublicationRepository;
use Illuminate\Support\Facades\DB;

/**
 * PublicationService
 *
 * Implements all business rules for the Publications Registry
 * module: BR-008 DOI uniqueness, author-membership validation
 * (FR-PUB-008), the linear status lifecycle (FR-PUB-005), and
 * citation count updates (FR-PUB-004).
 *
 * SDD Reference: RGMS SDD §3.6.2, §3.6.5
 */
final class PublicationService
{
    /**
     * Strict linear status lifecycle (SDD §3.6.2, literal wording):
     * each status advances only to the single next status.
     * Retracted is reachable only from Published and is terminal.
     *
     * @var array<string, string|null>
     */
    private const NEXT_STATUS = [
        Publication::STATUS_IN_PREPARATION => Publication::STATUS_SUBMITTED,
        Publication::STATUS_SUBMITTED => Publication::STATUS_UNDER_REVIEW,
        Publication::STATUS_UNDER_REVIEW => Publication::STATUS_ACCEPTED,
        Publication::STATUS_ACCEPTED => Publication::STATUS_PUBLISHED,
        Publication::STATUS_PUBLISHED => Publication::STATUS_RETRACTED,
        Publication::STATUS_RETRACTED => null,
    ];

    public function __construct(
        private readonly PublicationRepository $repository,
        private readonly GroupMembershipRepository $membershipRepository,
    ) {
    }

    /**
     * Register a new publication with its ordered authors.
     *
     * @param array<string, mixed> $data
     * @param list<string> $authorUimpIds
     */
    public function register(string $groupId, array $data, array $authorUimpIds): Publication
    {
        if (! empty($data['doi'])) {
            $this->assertDoiIsUnique($data['doi']);
        }

        $this->validateAuthorsAreMemberOfGroup($groupId, $authorUimpIds);

        return DB::transaction(function () use ($groupId, $data, $authorUimpIds): Publication {
            $publication = $this->repository->create([
                ...$data,
                'research_group_id' => $groupId,
                'status' => $data['status'] ?? Publication::STATUS_IN_PREPARATION,
            ]);

            foreach ($authorUimpIds as $order => $memberUimpId) {
                PublicationAuthor::create([
                    'publication_id' => $publication->id,
                    'member_uimp_id' => $memberUimpId,
                    'author_order' => $order + 1,
                ]);
            }

            AuditLog::record('CREATE', 'publications', $publication->id, null, $data);
             Cache::tags(['analytics:publications'])->flush();
            return $publication;
        });
    }

    /**
     * Update mutable attributes of a publication (status is
     * excluded — see transition()).
     *
     * @param array<string, mixed> $data
     */
    public function update(Publication $publication, array $data): Publication
    {
        if (! empty($data['doi']) && $data['doi'] !== $publication->doi) {
            $this->assertDoiIsUnique($data['doi']);
        }

        $oldValues = $publication->only(array_keys($data));

        return DB::transaction(function () use ($publication, $data, $oldValues): Publication {
            $updated = $this->repository->update($publication, $data);

            AuditLog::record('UPDATE', 'publications', $updated->id, $oldValues, $data);
Cache::tags(['analytics:publications'])->flush();
            return $updated;
        });
    }

    /**
     * Transition a publication's status to the next stage in the
     * linear lifecycle (FR-PUB-005).
     */
    public function transition(Publication $publication, string $newStatus): Publication
    {
        $currentStatus = $publication->status;
        $allowedNext = self::NEXT_STATUS[$currentStatus] ?? null;

        if ($allowedNext === null || $newStatus !== $allowedNext) {
            throw new InvalidStateTransitionException(
                sprintf('Cannot transition publication from %s to %s.', $currentStatus, $newStatus),
                from: $currentStatus,
                to: $newStatus,
            );
        }

        return DB::transaction(function () use ($publication, $currentStatus, $newStatus): Publication {
            $updated = $this->repository->update($publication, ['status' => $newStatus]);

            AuditLog::record(
                'TRANSITION',
                'publications',
                $updated->id,
                ['status' => $currentStatus],
                ['status' => $newStatus],
            );
Cache::tags(['analytics:publications'])->flush();
            return $updated;
        });
    }

    /**
     * Atomically update a publication's citation count and
     * citation_updated_at timestamp (FR-PUB-004).
     */
    public function updateCitationCount(Publication $publication, int $citationCount): Publication
    {
        return DB::transaction(function () use ($publication, $citationCount): Publication {
            $oldCount = $publication->citation_count;

            $updated = $this->repository->update($publication, [
                'citation_count' => $citationCount,
                'citation_updated_at' => now(),
            ]);

            AuditLog::record(
                'UPDATE',
                'publications',
                $updated->id,
                ['citation_count' => $oldCount],
                ['citation_count' => $citationCount],
            );

            return $updated;
        });
    }

    /**
     * Soft delete a publication.
     */
    public function softDelete(Publication $publication): bool
    {
        return DB::transaction(function () use ($publication): bool {
            $result = $this->repository->softDelete($publication);

            AuditLog::record('DELETE', 'publications', $publication->id, $publication->toArray(), null);

            return $result;
        });
    }

    /**
     * Enforce BR-008: DOI must be unique among active (non-deleted)
     * publications.
     */
    private function assertDoiIsUnique(string $doi): void
    {
        if ($this->repository->findByDoi($doi) !== null) {
            throw new ConflictException("A publication with DOI {$doi} already exists.");
        }
    }

    /**
     * Verify each submitted author is an active member of the owning
     * research group (FR-PUB-008).
     *
     * @param list<string> $authorUimpIds
     */
    private function validateAuthorsAreMemberOfGroup(string $groupId, array $authorUimpIds): void
    {
        foreach ($authorUimpIds as $memberUimpId) {
            if ($this->membershipRepository->findByMemberAndGroup($memberUimpId, $groupId) === null) {
                throw new ConflictException(
                    "UIMP member {$memberUimpId} is not an active member of this research group.",
                );
            }
        }
    }
}