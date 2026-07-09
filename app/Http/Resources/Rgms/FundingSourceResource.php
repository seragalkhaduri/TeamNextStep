<?php

declare(strict_types=1);

namespace App\Http\Resources\Rgms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * FundingSourceResource
 *
 * @property-read \App\Models\FundingSource $resource
 *
 * SDD Reference: RGMS SDD §3.5.3, §4.2.5
 */
final class FundingSourceResource extends JsonResource
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
            'research_group_id' => $this->research_group_id,
            'agency_name' => $this->agency_name,
            'grant_reference' => $this->grant_reference,
            'allocated_amount' => $this->allocated_amount,
            'currency_code' => $this->currency_code,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}