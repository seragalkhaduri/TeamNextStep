<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use App\Domain\ResearchGroups\Services\Clients\UimpMasterDataClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PatentResource
 *
 * @property-read \App\Models\Patent $resource
 *
 * SDD Reference: RGMS SDD §3.7.3
 */
final class PatentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $inventors = $this->relationLoaded('patentInventors')
            ? $this->patentInventors
            : $this->patentInventors()->orderBy('inventor_order')->get();

        $client = app(UimpMasterDataClient::class);

        return [
            'id' => $this->id,
            'research_group_id' => $this->research_group_id,
            'title' => $this->title,
            'patent_number' => $this->patent_number,
            'registration_authority' => $this->registration_authority,
            'filing_date' => $this->filing_date?->toDateString(),
            'grant_date' => $this->grant_date?->toDateString(),
            'status' => $this->status,
            'inventors' => $inventors->sortBy('inventor_order')->values()->map(fn ($inventor): array => [
                'member_uimp_id' => $inventor->member_uimp_id,
                'display_name' => $client->getAcademicStaff($inventor->member_uimp_id)?->displayName()
                    ?? $client->getStudent($inventor->member_uimp_id)?->displayName(),
                'inventor_order' => $inventor->inventor_order,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}