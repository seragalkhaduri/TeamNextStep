<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use App\Domain\ResearchGroups\Services\Clients\UimpMasterDataClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PublicationResource
 *
 * @property-read \App\Models\Publication $resource
 *
 * SDD Reference: RGMS SDD §3.6.3
 */
final class PublicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $authors = $this->relationLoaded('publicationAuthors')
            ? $this->publicationAuthors
            : $this->publicationAuthors()->orderBy('author_order')->get();

        $client = app(UimpMasterDataClient::class);

        return [
            'id' => $this->id,
            'research_group_id' => $this->research_group_id,
            'title' => $this->title,
            'publication_type' => $this->publication_type,
            'publication_year' => $this->publication_year,
            'status' => $this->status,
            'doi' => $this->doi,
            'journal_name' => $this->journal_name,
            'conference_name' => $this->conference_name,
            'issn' => $this->issn,
            'publisher' => $this->publisher,
            'impact_factor' => $this->impact_factor,
            'citation_count' => $this->citation_count,
            'citation_updated_at' => $this->citation_updated_at?->toIso8601String(),
            'authors' => $authors->sortBy('author_order')->values()->map(fn ($author): array => [
                'member_uimp_id' => $author->member_uimp_id,
                'display_name' => $client->getAcademicStaff($author->member_uimp_id)?->displayName()
                    ?? $client->getStudent($author->member_uimp_id)?->displayName(),
                'author_order' => $author->author_order,
                'contribution_type' => $author->contribution_type,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}