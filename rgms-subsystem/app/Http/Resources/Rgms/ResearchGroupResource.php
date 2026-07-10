<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use App\Domain\ResearchGroups\Services\Clients\UimpMasterDataClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ResearchGroupResource
 *
 * @property-read \App\Models\ResearchGroup $resource
 *
 * SDD Reference: RGMS SDD §3.1.11
 */
final class ResearchGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'group_name' => $this->group_name,
            'research_field' => $this->research_field,
            'research_area' => $this->research_area,
            'status' => $this->status,
            'pi_staff_id' => $this->pi_staff_id,
            'pi_display_name' => $this->resolvePiDisplayName(),
            'department_ref_id' => $this->department_ref_id,
            'budget_allocation' => $this->budget_allocation,
            'funding_source' => FundingSourceResource::make($this->whenLoaded('fundingSources')?->first()),
            'member_count' => $this->when(
                $this->relationLoaded('groupMemberships'),
                fn (): int => $this->groupMemberships->count(),
                fn (): int => $this->groupMemberships()->count(),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Resolve the PI's display name from cached UIMP master data
     * (SDD §3.14.5 — UimpMasterDataClient, 5-60 min cache TTL).
     */
    private function resolvePiDisplayName(): ?string
    {
        $staff = app(UimpMasterDataClient::class)->getAcademicStaff($this->pi_staff_id);

        return $staff?->displayName();
    }
}