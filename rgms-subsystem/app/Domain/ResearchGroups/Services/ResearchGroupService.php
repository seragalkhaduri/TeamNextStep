<?php

declare(strict_types=1);

namespace App\Domain\ResearchGroups\Services;

use App\Domain\ResearchGroups\Exceptions\BlockedDeletionException;
use App\Domain\ResearchGroups\Exceptions\DuplicateResearchGroupException;
use App\Domain\ResearchGroups\Exceptions\IneligiblePIException;
use App\Domain\ResearchGroups\Exceptions\InvalidStateTransitionException;
use App\Domain\ResearchGroups\Jobs\SendUimpNotification;
use App\Domain\ResearchGroups\Models\BudgetAllocation;
use App\Domain\ResearchGroups\Models\FundingSource;
use App\Domain\ResearchGroups\Models\GroupStatusHistory;
use App\Domain\ResearchGroups\Models\LocalAuditEntry;
use App\Domain\ResearchGroups\Models\ResearchGroup;
use App\Domain\ResearchGroups\Models\ResearchProject;
use App\Domain\ResearchGroups\Repositories\ResearchGroupRepository;
use App\Domain\ResearchGroups\Services\Clients\UimpMasterDataClient;
use App\Domain\ResearchGroups\Services\Support\PdfReportGenerator;
use App\Domain\ResearchGroups\Services\Support\XlsxReportGenerator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ResearchGroupService
 *
 * Implements all business rules for the Research Groups Management
 * module: PI eligibility (via UIMP), BR-007 duplicate constraint,
 * governed lifecycle transitions, blocked-deletion guarding, full
 * attribute change history, and PDF/XLSX export.
 *
 * SDD Reference: RGMS SDD §3.1.2, §3.1.7
 */
final class ResearchGroupService
{
    /**
     * Allowed lifecycle state transitions (confirmed business rule —
     * not explicitly stated in SDD §3.1, resolved by direct instruction):
     * Draft -> Active; Active <-> Suspended; Active/Suspended -> Archived;
     * Archived is terminal (no outgoing transitions).
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED_TRANSITIONS = [
        ResearchGroup::STATUS_DRAFT => [ResearchGroup::STATUS_ACTIVE],
        ResearchGroup::STATUS_ACTIVE => [
            ResearchGroup::STATUS_SUSPENDED,
            ResearchGroup::STATUS_ARCHIVED,
        ],
        ResearchGroup::STATUS_SUSPENDED => [
            ResearchGroup::STATUS_ACTIVE,
            ResearchGroup::STATUS_ARCHIVED,
        ],
        ResearchGroup::STATUS_ARCHIVED => [],
    ];

    /**
     * Statuses that require a justification string (SDD §3.1.12).
     *
     * @var list<string>
     */
    private const JUSTIFICATION_REQUIRED_FOR = [
        ResearchGroup::STATUS_SUSPENDED,
        ResearchGroup::STATUS_ARCHIVED,
    ];

    public function __construct(
        private readonly ResearchGroupRepository $repository,
        private readonly UimpMasterDataClient $uimpClient,
        private readonly PdfReportGenerator $pdfGenerator,
        private readonly XlsxReportGenerator $xlsxGenerator,
    ) {
    }

    /**
     * Paginate research groups matching the given filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    /**
     * Create a new research group.
     *
     * 1. Validate PI eligibility via UIMP Academic Staff API.
     * 2. Enforce BR-007 (no duplicate Active PI+Field+Area in the
     *    current fiscal year).
     * 3. Persist within a transaction, write local audit log, and
     *    dispatch async UIMP audit/notification jobs.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): ResearchGroup
    {
        $staff = $this->uimpClient->getAcademicStaff($data['pi_staff_id']);

        if (! $staff->isEligibleForPI()) {
            throw new IneligiblePIException('Staff member does not meet PI eligibility criteria.');
        }

        $this->assertNoDuplicateActiveGroup(
            $data['pi_staff_id'],
            $data['research_field'],
            $data['research_area'],
        );

        return DB::transaction(function () use ($data): ResearchGroup {
            $group = $this->repository->create($data);

            AuditLog::record('CREATE', 'research_groups', $group->id, null, $data);

            dispatch(new SendUimpNotification(
                [$group->pi_staff_id],
                'research_group.created',
                ['entity_id' => $group->id],
            ));

            return $group;
        });
    }

    /**
     * Update mutable attributes of a research group (status changes
     * are excluded — see transition()).
     *
     * @param array<string, mixed> $data
     */
    public function update(ResearchGroup $group, array $data): ResearchGroup
    {
        $oldValues = $group->only(array_keys($data));

        return DB::transaction(function () use ($group, $data, $oldValues): ResearchGroup {
            $updated = $this->repository->update($group, $data);

            AuditLog::record('UPDATE', 'research_groups', $updated->id, $oldValues, $data);

            return $updated;
        });
    }

    /**
     * Transition a research group to a new lifecycle status,
     * recording the transition in GroupStatusHistory and notifying
     * the PI and Research Administrator (FR-RES-009).
     */
    public function transition(ResearchGroup $group, string $newStatus, ?string $justification): ResearchGroup
    {
        $currentStatus = $group->status;

        if (! in_array($newStatus, self::ALLOWED_TRANSITIONS[$currentStatus] ?? [], true)) {
            throw new InvalidStateTransitionException(
                sprintf('Cannot transition research group from %s to %s.', $currentStatus, $newStatus),
                from: $currentStatus,
                to: $newStatus,
            );
        }

        if (in_array($newStatus, self::JUSTIFICATION_REQUIRED_FOR, true) && blank($justification)) {
            throw new InvalidStateTransitionException(
                sprintf('A justification is required when transitioning to %s.', $newStatus),
                from: $currentStatus,
                to: $newStatus,
            );
        }

        return DB::transaction(function () use ($group, $currentStatus, $newStatus, $justification): ResearchGroup {
            $group = $this->repository->update($group, ['status' => $newStatus]);

            GroupStatusHistory::create([
                'group_id' => $group->id,
                'old_status' => $currentStatus,
                'new_status' => $newStatus,
                'justification' => $justification,
                'transitioned_by' => (string) Auth::id(),
                'transitioned_at' => now(),
            ]);

            AuditLog::record(
                'UPDATE',
                'research_groups',
                $group->id,
                ['status' => $currentStatus],
                ['status' => $newStatus],
            );

            dispatch(new SendUimpNotification(
                [$group->pi_staff_id],
                'research_group.status_changed',
                [
                    'entity_id' => $group->id,
                    'old_status' => $currentStatus,
                    'new_status' => $newStatus,
                ],
            ));

            return $group;
        });
    }

    /**
     * Soft delete a research group, blocking the operation if active
     * projects, active memberships, or financial commitments exist
     * (FR-RES-006).
     */
    public function softDelete(ResearchGroup $group): bool
    {
        $activeMemberships = $this->repository->countActiveMemberships($group->id);

        $activeProjects = $group->researchProjects()
            ->whereIn('status', [
                ResearchProject::STATUS_PLANNING,
                ResearchProject::STATUS_ACTIVE,
                ResearchProject::STATUS_ON_HOLD,
            ])
            ->count();

        // "Financial commitments" (FR-RES-006) interpreted per actual
        // schema: an Active funding source, or any recorded budget
        // allocation — budget_allocations has no status column to
        // distinguish "open" vs "closed" (SDD §4.2.6).
        $activeFundingSources = $group->fundingSources()
            ->where('status', FundingSource::STATUS_ACTIVE)
            ->count();

        $budgetAllocations = BudgetAllocation::query()
            ->where('research_group_id', $group->id)
            ->count();

        if ($activeMemberships > 0 || $activeProjects > 0 || $activeFundingSources > 0 || $budgetAllocations > 0) {
            throw new BlockedDeletionException(
                'Cannot delete research group with active dependencies.',
                counts: [
                    'active_memberships' => $activeMemberships,
                    'active_projects' => $activeProjects,
                    'active_funding_sources' => $activeFundingSources,
                    'budget_allocations' => $budgetAllocations,
                ],
            );
        }

        return DB::transaction(function () use ($group): bool {
            $result = $this->repository->softDelete($group);

            AuditLog::record('DELETE', 'research_groups', $group->id, $group->toArray(), null);

            return $result;
        });
    }

    /**
     * Retrieve the full attribute change history for a research group
     * (FR-RES-008), flattened from local_audit_log_rgms into
     * per-field { field, old_value, new_value, changed_by, changed_at }
     * entries (SDD §5.2 GET .../history response schema).
     */
    public function history(ResearchGroup $group): Collection
    {
        return LocalAuditEntry::query()
            ->where('entity_type', 'research_groups')
            ->where('entity_id', $group->id)
            ->orderBy('recorded_at')
            ->get()
            ->flatMap(function (LocalAuditEntry $entry): array {
                $old = $entry->old_value ?? [];
                $new = $entry->new_value ?? [];
                $changedFields = array_unique([...array_keys($old), ...array_keys($new)]);

                return collect($changedFields)
                    ->map(fn (string $field): array => [
                        'field' => $field,
                        'old_value' => $old[$field] ?? null,
                        'new_value' => $new[$field] ?? null,
                        'changed_by' => $entry->user_id,
                        'changed_at' => $entry->recorded_at->toIso8601String(),
                    ])
                    ->all();
            });
    }

    /**
     * Export research groups matching the given filters as a PDF or
     * XLSX file (FR-RES-012), scoped to the requester's RBAC access
     * (enforced by ResearchGroupPolicy::export() prior to this call).
     *
     * @param array<string, mixed> $filters
     */
    public function export(array $filters, string $format): StreamedResponse
    {
        $groups = $this->repository->findForExport($filters);
        $filename = 'research-groups-export-' . now()->format('Y-m-d');

        return $format === 'xlsx'
            ? $this->xlsxGenerator->generate($groups, $filename)
            : $this->pdfGenerator->generate($groups, $filename);
    }

    /**
     * Enforce BR-007: no two Active research groups may share the
     * same PI, Research Field, and Research Area within the current
     * fiscal year.
     */
    private function assertNoDuplicateActiveGroup(string $piStaffId, string $field, string $area): void
    {
        $currentYear = (int) now()->format('Y');

        $existing = $this->repository->findByPiFieldArea($piStaffId, $field, $area, $currentYear);

        $activeDuplicate = $existing->firstWhere('status', ResearchGroup::STATUS_ACTIVE);

        if ($activeDuplicate !== null) {
            throw new DuplicateResearchGroupException(
                'An Active research group with the same PI, Field, and Area already exists for this fiscal year.',
                existingId: $activeDuplicate->id,
            );
        }
    }
}