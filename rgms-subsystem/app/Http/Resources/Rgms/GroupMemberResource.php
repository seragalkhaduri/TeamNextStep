<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use App\Domain\ResearchGroups\Models\GroupMembership;
use App\Domain\ResearchGroups\Services\Clients\UimpMasterDataClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * GroupMemberResource
 *
 * @property-read GroupMembership $resource
 *
 * SDD Reference: RGMS SDD §3.2.11
 */
final class GroupMemberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'membership_id' => $this->id,
            'group_id' => $this->group_id,
            'member_uimp_id' => $this->member_uimp_id,
            'member_display_name' => $this->resolveMemberDisplayName(),
            'member_type' => $this->member_type,
            'role' => $this->role,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'workload_percentage' => $this->workload_percentage,
            'status' => $this->status,
            'is_over_allocated' => $this->resource->isOverAllocated(),
        ];
    }

    /**
     * Resolve the member's display name from cached UIMP master data
     * (SDD §3.14.5). Tries Academic Staff first, then Student.
     */
    private function resolveMemberDisplayName(): ?string
    {
        $client = app(UimpMasterDataClient::class);

        if ($this->member_type === GroupMembership::MEMBER_TYPE_STAFF) {
            return $client->getAcademicStaff($this->member_uimp_id)?->displayName();
        }

        if ($this->member_type === GroupMembership::MEMBER_TYPE_STUDENT) {
            return $client->getStudent($this->member_uimp_id)?->displayName();
        }

        return null;
    }
}