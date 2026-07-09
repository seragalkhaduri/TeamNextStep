<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use App\Domain\ResearchGroups\Services\Clients\UimpMasterDataClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ProjectContributorResource
 *
 * @property-read \App\Models\ProjectContributor $resource
 *
 * SDD Reference: RGMS SDD §3.3.11
 */
final class ProjectContributorResource extends JsonResource
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
            'project_id' => $this->project_id,
            'member_uimp_id' => $this->member_uimp_id,
            'member_display_name' => $this->resolveMemberDisplayName(),
            'contributor_role' => $this->contributor_role,
        ];
    }

    /**
     * Resolve the contributor's display name from cached UIMP master
     * data (SDD §3.14.5). Tries Academic Staff first, then Student.
     */
    private function resolveMemberDisplayName(): ?string
    {
        $client = app(UimpMasterDataClient::class);

        return $client->getAcademicStaff($this->member_uimp_id)?->displayName()
            ?? $client->getStudent($this->member_uimp_id)?->displayName();
    }
}